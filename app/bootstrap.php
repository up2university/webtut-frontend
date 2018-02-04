<?php

require_once 'libs/AppLog.php';
require_once 'libs/Locale.php';
require_once 'libs/SAMLSession.php';
require_once 'libs/IceTurnRestApi.php';
require_once 'libs/WebMailer.php';

// Setup custom Twig view
//$twigView = new \Slim\Extras\Views\Twig();

// Prepare app
$app = new \Slim\Slim(array(    
    'mode' => 'development',
    //'view' => $twigView,
    'templates.path' => '../templates',
));

// Prepare view
$app->view(new \Slim\Views\Twig());

$app->view()->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true,
    'debug' => true,
);

$app->view()->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
    new \JSW\Twig\TwigExtension(),
    new Twig_Extensions_Extension_I18n(),
    new Twig_Extension_Debug()
  );

  
// Only invoked if mode is "production"
$app->configureMode('production', function () use ($app) {
    $app->config(array(
        'log.enable' => true,
        'debug' => false
    ));
});

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app) {
    $app->config(array(
        'log.enable' => false,
        'debug' => true
    ));
});


// Create monolog logger and store logger in container as singleton 
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function () {
    $log = new \Monolog\Logger('slim-skeleton');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Monolog\Logger::DEBUG));
    return $log;
});

$app->notFound(function () use ($app) {
    
    $app->render('404.html.twig', [
    'message' => \Libs\Locale::getHtmlContent("error_404")]);
});

// Handle Locale Cookie
$app->add(new Libs\Locale());
$app->add(new Libs\SAMLSession());

$app->view()->set('config', \SiteConfig::getInstance()->all());
    
$filter  = new Twig_SimpleFilter("cast_to_array", function($stdClassObject) {    
    $response = array();
    foreach ($stdClassObject as $key => $value) {
      $response[] = array($key, $value);
    }
    return $response;
  });
  
$app->view()->getEnvironment()->addFilter($filter);

$filter  = new Twig_SimpleFilter("type", function($stdClassObject) {        
    return gettype($stdClassObject);
  });
  
$app->view()->getEnvironment()->addFilter($filter);

$filter  = new Twig_SimpleFilter("translate", function($stdClassObject) {        
    return _($stdClassObject);
  });
  
$app->view()->getEnvironment()->addFilter($filter);

return $app;
