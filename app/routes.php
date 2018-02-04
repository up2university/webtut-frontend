
<?php

// Define routes

$app->get('/', function () use ($app) {  
  $app->redirect(\SiteConfig::getInstance()->get("base_path") . '/home');
});

//about page
$app->get('/about', function () use ($app){
  $app->render('about.html.twig', [
    'description' => \Libs\Locale::getHtmlContent("about_text")]);
});

$app->group('/doc', function() use ($app) {  
    require_once "routes/doc.php";
});

$app->group('/home', function() use ($app) {  
    require_once "routes/app.php";
});

$app->group('/utils', function() use ($app) {
    require_once "routes/utils.php";
});

$app->group('/tut', function() use ($app) {   
    require_once "routes/tut.php";
});

$app->group('/embbed', function() use ($app) {      
    require_once "routes/embbed.php";    
});

$app->group('/profile', function() use ($app) {      
    require_once "routes/profile.php";    
});

$app->group('/admin', function() use ($app) {  
    require_once "routes/admin.php";    
});

function APIrequest(){
        $app = \Slim\Slim::getInstance();

        $app->view(new \JsonApiView());
        $app->add(new \JsonApiMiddleware());
        
        $app->view()->clear("config");
        $app->view()->clear("lang");
        $app->view()->clear("ss");

}

$app->group('/api', 'APIrequest', function() use ($app) {  
    require_once "routes/api.php";    
});
