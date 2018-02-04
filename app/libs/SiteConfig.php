<?php

define("LIBPATH", dirname(__FILE__) . DIRECTORY_SEPARATOR);
define("HOMEPATH", LIBPATH . "../");
define("CONFIG_FILE", HOMEPATH . "config.php");

class SiteConfig {

  private $configs;  
  private static $instance;
    
  public function __construct($array = null) {
    $this->configs = $array;
  }
  
  public static function getInstance() {
  	  	
  	if (!SiteConfig::$instance instanceof self) {
  		//Load config from user      
  		include CONFIG_FILE;

      $c["api_path"] = $c["backend_protocol"] . "://" . $c["backend_host"] . ":" . $c["backend_port"] . $c["backend_api_path"];
  
      SiteConfig::$instance = new self($c);
  	}

  	return SiteConfig::$instance;
  }
  
  
  public function set($key, $value) {
    $this->configs[$key] = $value;
  }
  
  public function get($key) {
    if (!isset($this->configs[$key]))
      throw new Exception("Unknown config variable ['$key']");
    
    return $this->configs[$key];    
  }

  public function dump()
  {
    return var_export($this->configs);
  }

  public function all()
  {
    return $this->configs;
  }
  
}

?>