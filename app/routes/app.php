<?php

$app->get('/', function () use ($app){
  
  $app->render('index.html.twig', [
    'description' => \Libs\Locale::getHtmlContent("homepage_text")]);
        
});


