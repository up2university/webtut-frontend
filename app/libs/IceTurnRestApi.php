<?php

class IceTurnRestAPI
{
  private $serverURL;
  private $apiKey;
  private $serverIP;
  private $appData;
  
  private $uris;
  private $username;
  private $password;
  private $credentialType;
  
  private $updated;
    
  public function __construct($serverURL, $apiKey) 
  { 
    $this->serverURL = $serverURL;
    $this->apiKey = $apiKey;
    $this->serverIP = $_SERVER["REMOTE_ADDR"];
    $this->appData = null;
    
    $updated = false;
  }
  
  private function getClientIP() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
  }

  public function setAppData($appData)
  {
    $this->appData = $appData;
  }
  
  public function getApiURL($clientIP = null)  
  {
    if ($clientIP == null) {
      $clientIP = $this->getClientIP();
    }
    
    $url = $this->serverURL . "&api_key=" . $this->apiKey;
    
    if(isset($this->appData)) {
      $url .= "&ufrag=".$this->appData;
    }

    if(isset($clientIP)) {
      $url .= "&ip=".$clientIP;
    }
        
    return $url;
  }
  
  public function getIceServers($clientIP = null) 
  {
    $url = $this->getApiURL($clientIP);

    // create curl resource
    $ch = curl_init();

    // set url
    curl_setopt($ch, CURLOPT_URL, $url);

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // $output contains the output string
    $output = curl_exec($ch);

    //handle curl error
    if($output === false) {        
        throw new Exception('ICE TURN REST API Failed call : ' . curl_error($ch));
    }

    // close curl resource to free up system resources
    curl_close($ch);
	
    $response = json_decode($output,true);

    //handle curl error
    if(isset($response["error"])) {
        throw new Exception('ICE TURN REST API Failed call : ' . $response["error"]["text"]);
    }
    
    $this->uris = $response["uris"];
    $this->username = $response["username"];
    $this->password = $response["password"];
    $this->credentialType = "password";
    $result[] = array("urls" => $response["uris"], "username"=> $response["username"], "credential"=> $response["password"], "credentialType" => "password");

    $this->updated = true;
    return $result;
  }
  
  public function getURIs()          
  { 
    return $this->uris; 
  }
  
  public function getUsername() 
  {   
    return $this->username; 
  }
  
  public function getPassword() 
  { 
    return $this->password; 
  }
  
  public function getCredentialType() 
  { 
    return $this->credentialType; 
  }
  
  private function getReconstructedURI($parsed_uri) 
  {
    $scheme   = isset($parsed_uri['scheme']) ? $parsed_uri['scheme'] . '://' : '';
    $host     = isset($parsed_uri['host']) ? $parsed_uri['host'] : '';
    $port     = isset($parsed_uri['port']) ? ':' . $parsed_uri['port'] : '';
    $user     = isset($parsed_uri['user']) ? $parsed_uri['user'] : '';
    $pass     = isset($parsed_uri['pass']) ? ':' . $parsed_uri['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_uri['path']) ? $parsed_uri['path'] : '';
    $query    = isset($parsed_uri['query']) ? '?' . $parsed_uri['query'] : '';
    $fragment = isset($parsed_uri['fragment']) ? '#' . $parsed_uri['fragment'] : '';
    
    $uri = "$scheme$user$pass$host$port$path$query$fragment";
    
    return $uri;
  } 
  
  public function getPeerJSConfig() 
  {
    $result = array();
    
    if (!$this->updated) 
      $this->getIceServers();
    
    foreach($this->uris as $uri) {            
      $result[] = array("url" => $uri, "username" => $this->getUsername(), "credential" => $this->getPassword());
    }

    return json_encode($result);    
  }
  
}  



