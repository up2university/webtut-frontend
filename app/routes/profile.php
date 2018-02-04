<?php


$app->get('/me', function () use ($app){

    $session = Libs\SAMLSession::getInstance();

    $app->render('userprofile.html.twig', [      
    ]);
    
});


?>
