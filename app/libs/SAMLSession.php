<?php

namespace Libs;

if(getenv("SIMPLESAML_AUTOLOAD_PATH"))
	require_once(getenv("SIMPLESAML_AUTOLOAD_PATH"));
else
	require_once(\SiteConfig::getInstance()->get("ssp_base_path").'/lib/_autoload.php');


class SAMLSession extends \Slim\Middleware
{

  private $as;
  private $session;
  private $authenticated;
  private $user;
  
  private static $instance;
  
  private function __clone() {}

  public function __construct() { 
  
    $this->as = new \SimpleSAML_Auth_Simple(\SiteConfig::getInstance()->get('sp-default'));
    
/*    
    if ($this->as->isAuthenticated()) {
      $this->attributes = $this->as->getAttributes();
      $this->authenticated = true;
    } else {
      $this->authenticated = false;
      $this->user = null;
    }
*/
    $this->authenticated = false;
    $this->user = null;
  }

  public function call()
  {       
    $app = \Slim\Slim::getInstance();
    $session = SAMLSession::getInstance(false);

    $result = $session->getAttributes();
    $attr = array();
    foreach($result as $k=>$v)
      $attr[$k] = $v[0];
      
    $attr["admin"] = $this->isAdmin();
    $attr["authenticated"] = $this->isAuthenticated();
    $attr["friendlyName"] = $this->getFriendlyName();    
    $app->view()->set("ss", $attr);
    $this->next->call();
  }
  
  public static function getInstance($enforce_auth = true) {
    
  	if (!SAMLSession::$instance instanceof self) {
  		SAMLSession::$instance = new self();
  	}
  	
    SAMLSession::$instance->requireAuth($enforce_auth);
      	
  	return SAMLSession::$instance;
  }

  public function getUser()
  {
    return $this->user;
  }
  
  public function requireAuth($enforce_auth = true) 
  {    
    // $this->as->requireAuth();
    //$this->session = SAMLSession::getInstance(true);
    // $this->authenticated = $this->isAuthenticated();
    // SAMLSession::$instance->authenticated = false;

    $this->authenticated = SAMLSession::$instance->isAuthenticated();
    
    if (($enforce_auth) && (!$this->authenticated)) {
      
      $this->as->requireAuth();
      $this->authenticated = SAMLSession::$instance->isAuthenticated();
      
    }
        
    if (($this->user == null) && ($this->authenticated)) {
      $user = \Model::factory('User')->where('email', $this->getEmail())->find_one();
        
      $already_authenticated = false;

      if ($user == false) {
        $user = \Model::factory('User')->create();
        $user->email = $this->getEmail();
        $user->name = $this->getFriendlyName();      
        $user->localUser = 0;
        $user->sessionCount = 0;
        $user->authSource = $this->getAuthSourceId();       
        $user->createdAt = date( 'Y-m-d H:i:s', time() );            
      } else {
        $already_authenticated = $user->inSession;
      }
      
      if ($already_authenticated == false) {
        $user->locale = Locale::getCurrentLang();
        $user->lastLogin = date( 'Y-m-d H:i:s', time() );
        $user->inSession = true;
        $user->sessionCount++;
        $user->save();
        
        AppLog("login", $user);
      }
      
      $this->user = $user;
      
    }
    
  }

  public function getSession() {
    return $this->as;
  }

  public function hasAllAttributes() {
  	
  	$result = true;
  	
  	foreach($this->getAllExpectedAttributes() as $attribute => $params) {
  		if (!$this->findAttribute($attribute)) {
  			$result = false;
  		}
  	}
  	
  	return $result;
  }

  public function hasMinimumAttributes() {

  	$result = true;
  	 
  	foreach($this->getAllExpectedAttributes() as $attribute => $params) {
  		if (!$this->findAttribute($attribute)) {        
  			if ($params["mandatory"] == 1)
  			  $result = false;
  		}
  	}
    
  	if ($result) {
  	  foreach($this->getAllExpectedAttributes() as $attribute => $params) {
        if ($v = $this->findAttribute($attribute)) {
  		  if (isset($params["regex"])) {
  		    $ok = preg_match("/" . $params["regex"] . "/",$v);
  		    
  		    if (!$ok) {
  		      $result = false;
  		    }
  		  }  		  
        }
  	  }
  	}    

  	return $result;
  }   
  
  public function getAllExpectedAttributes()
  {     
  	return \SiteConfig::getInstance()->get('sp-expected-attributes');
  }

  public function getAdministratorList()
  {    
    return \SiteConfig::getInstance()->get("sp-administrator-list");
  }  
  
  public function isAuthenticated() {    
    return $this->hasMinimumAttributes();
  }

  public function userIsAuthenticated() {  	
  	return $this->hasMinimumAttributes();
  }
  
  public function getAttributes() {
    return $this->as->getAttributes();
  }

  public function getFQDN_AuthSourceId()
  {
    return preg_replace('/https:\/\/([0-9A-Za-z\-\.]+)\/.*/', '$1', $this->getAuthId());
  }

  public function getAuthSourceId()
  {    
  	if($this->as->getAuthSource()  != null)
    	return $this->as->getAuthSource()->getAuthId();
  	else 
  		return "";
  }
  
  public function findAttribute($attr_id) {
    
    $attributes = $this->getAttributes();
    
    if (isset($attributes[$attr_id][0]))
      return $attributes[$attr_id][0];
    
    return null;
  }
  
  public function getUniqueID()
  {
  	return $this->findAttribute("eduPersonPrincipalName");  	
  }
  
  public function getEmail() 
  {
    return $this->findAttribute("mail");
  }

  public function getEntityFQDN()
  {
    // Note! Change Federation available fields!!!
    return preg_replace('/.*\@(.*)/', '$1', $this->getEmail());
  }

  public function getMailDomain()
  {
    // Note! Change Federation available fields!!!
    return preg_replace('/.*\@(.*)/', '$1', $this->getEmail());
  }

  public function getMailID()
  {
    // Note! Change Federation available fields!!!
    return preg_replace('/(.*)\@.*/', '$1', $this->getEmail());
  }

  public function getDisplayName() 
  {
    return $this->findAttribute("displayName");
  }

  public function getAffiliation() 
  {
    return preg_replace('/(.*)\@.*/', '$1', $this->findAttribute("eduPersonScopedAffiliation"));
  }

  public function getGivenName() 
  {
    return $this->findAttribute("givenname");
  }

  public function getFriendlyName()
  {
    $result = $this->getDisplayName();	
  
    if ($result == "")
  	  $result = $this->getGivenName();        
    
    return $result;
  }
  
  public function getTelephone()
  {
    return $this->findAttribute("urn:oid:2.5.4.20");
  } 

  public function isAdmin()
  {
  	return ($this->isAuthenticated() 
         && in_array($this->getEmail(), \SiteConfig::getInstance()->get("app-administrator-list")));
  }
  
  public function logout()
  {
    $this->as->logout();
  }
  
}
