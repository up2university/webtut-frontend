<?php

namespace Libs;

/*
global $config;
include $config["install_path"] . "/app/libs/SimpleSaml.php";
*/
require_once('/var/simplesamlphp/lib/_autoload.php');

class SimpleSamlPHPAuth extends \Slim\Middleware
{
  protected $as;
  protected $attributes;
  
  public function __construct($SP)
  {   
    $this->as = new \SimpleSAML_Auth_Simple($SP);

    if ($this->as->isAuthenticated());
      $this->attributes = $this->as->getAttributes();
  }
  
  public function requireAuth()
  {
    $this->as->requireAuth();
  }
  
  public function call() 
  {       
    $this->requireAuth();
    var_dump($this->attributes);
    $this->next->call();
  }
}
