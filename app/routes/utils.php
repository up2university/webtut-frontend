<?php

$app->get('/setlang/:lang', function ($lang) use ($app) {
  foreach(\SiteConfig::getInstance()->get("locales") as $locale) {
    if (strtoupper($lang) == strtoupper($locale["label"])) {
      $app->setCookie('locale', $locale['locale']);      
    }
  }
  
  if ($app->request()->getReferrer() == "") {
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/");
  } else {
    $app->redirect($app->request()->getReferrer());
  }
});

$app->get('/login', function () use ($app) {
  
  $session = Libs\SAMLSession::getInstance(true);

  if ($session->isAuthenticated()) {
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . '/');
  } else
    $app->render('login-failed.html.twig', [
      'attributes' => $session->getAttributes()]);

});

$app->get('/logout', function () use ($app) {

  $session = Libs\SAMLSession::getInstance(false);

  if ($session->isAuthenticated()) {

    $user = Model::factory('User')->where('email',$session->getEmail())->find_one();
    
    if (($user != false) && (get_class($user) == "User")) {
      $user->inSession = false;
      $user->save();
      AppLog("logout", $user);      
    }
    
    $session->logout();

  } else
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/");
});


$app->get('/peerjs/id', function () use ($app) {
  $ts = $app->request()->params("ts");
  
  // original: http://0.peerjs.com:9000/peerjs/id?ts=14494509349750.21418951964005828
  // to request: http://0.peerjs.com:9000/1thkzeva7n0l766r/id?ts=14494511601160.13506693998351693
  
  $result = file_get_contents("http://" . \SiteConfig::getInstance()->get("peerjs_cloud_host") . 
                                    ":" . \SiteConfig::getInstance()->get("peerjs_cloud_port") . 
                                    "/" . \SiteConfig::getInstance()->get("peerjs_cloud_token") . 
                                    "/" . \SiteConfig::getInstance()->get("peerjs_cloud_path") . $ts);
  
  $app->response->setBody($result);
  
});

$app->post('/peerjs/(:token)/(:newtoken)/id', function ($token, $newtoken) use ($app) {
  
  $i = $app->request()->params("i");

  // https://webrtc-hub.fccn.pt/webtut/utils/peerjs/hrds75xdbhloko6r/dbnmzir6oxs6pqfr/id?i=1

  $opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $app->request()->post()
    )
  );

  $context  = stream_context_create($opts);

  $result = file_get_contents("http://" . \SiteConfig::getInstance()->get("peerjs_cloud_host") . 
                                    ":" . \SiteConfig::getInstance()->get("peerjs_cloud_port") . 
                                    "/peerjs" . 
                                    "/" . $token . 
                                    "/" . $newtoken .
                                    "/id?i=" . $i, false, $context);
  
  $app->response->setBody($result);
});