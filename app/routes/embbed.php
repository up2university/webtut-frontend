<?php

function newParticipation($session)
{
  $app =  \Slim\Slim::getInstance();
  
  $participation = Model::factory('Participation')->create();
  $participation->session_id = $session->id;
  $participation->meeting_id = null;
  $participation->user_id = null;
  $participation->participantIP = (\SiteConfig::getInstance()->get("gethostbyaddr")?gethostbyaddr($app->request->getIp()):$app->request->getIp());
  $participation->participantName = null;
  $participation->participantEmail = null;
  $participation->registered = date("Y-m-d H:i:s");
  $participation->startTime = '0000-00-00 00:00:00';
  $participation->endTime = '0000-00-00 00:00:00';
  $participation->feedback = null;
  $participation->secondsWaited = null;  
  $participation->queueState_id = Model::factory('QueueState')->where('label','lurking')->find_one()->id;
  $participation->peerJSId = null;
  $participation->token = md5(time() . "+" . \SiteConfig::getInstance()->get("token_hash"));
  
  if ($session->get("mustAuthenticate")) {
    $ss = Libs\SAMLSession::getInstance(true);
    $participation->participantName = $ss->getFriendlyName();
    $participation->participantEmail = $ss->getEmail();
    $participation->user_id = $ss->getUser()->id;
  } else {
    $ss = Libs\SAMLSession::getInstance(false);
    
    if (is_object($ss)) {
      if ($ss->isAuthenticated()) {
        $participation->participantName = $ss->getFriendlyName();
        $participation->participantEmail = $ss->getEmail();
        $participation->user_id = $ss->getUser()->id;
      }
    }
  }
  
  $participation->save();
  appLog("new participant", $participation);
  
  return $participation;
}
    
$app->get('/(:stoken)/js', function($stoken) use ($app) {  
    
  $session = Model::factory('Session')->where('token',$stoken)->find_one();

  if (!$session) {
    
    $app->render('clientError.html.twig', [
      "message" => \Libs\Locale::getHtmlContent("embbed-session-not-found")      
    ]);
    
  } else {
    
    // $participation = newParticipation($session);    
    
    $app->response->headers->set('Content-Type', 'application/javascript');
    
    $app->render('participant.js.twig', [
      "session" => $session
      // "participant" => $participation
    ]);

  }
  
});

$app->get('/(:token)/test', function($token) use ($app) {  

  $html = array(
             "html" => "<a href='" . \SiteConfig::getInstance()->get("full_url") . "/embbed/$token" . "'></a>",
             "script" => "<script src='" . \SiteConfig::getInstance()->get("full_url") . "/embbed/$token/js" . "'></script>",
             "testURL" => \SiteConfig::getInstance()->get("full_url") . "/embbed/$token/test");

  $app->render('testParticipant.html.twig', [        
    "embbedHtml" => $html    
  ]);
});

$app->get('/(:token)/client', function($token) use ($app) {  

  global $_SERVER;

  $session = Model::factory('Session')->where('token',$token)->find_one();

  if (!is_object($session)) {
    
    $app->render('clientError.html.twig', [
      "message" => \Libs\Locale::getHtmlContent("embbed-session-not-found")      
    ]);
    
  } else {
    
    $participation = newParticipation($session);    

    $widget = "clientParticipantEmbbed.html.twig";

	error_log(print_r($session->sessionLayout(),true));
		
    if ($session->sessionLayout()->label == "embed-autostart") {
      $widget = "clientParticipantPopupV2mini.html.twig";
      
      // Repeated from below, consider join code on a function
      $iceTurnApi = new IceTurnRestApi(\SiteConfig::getInstance()->get("stun_turn_rest_api_url"), \SiteConfig::getInstance()->get("stun_turn_rest_api_key"));      

      $app->render($widget, [        
        "session" => $session,
        "participant" => $participation,
        "meeting" => $participation->session()->activeMeeting(),
        "iceTurnApi" => $iceTurnApi
      ]);

    } else {
    
      if ($session->sessionLayout()->label == "embed") {
        $widget = "clientParticipantEmbbedAllIn.html.twig";
      }
    
      if ($session->sessionLayout()->label == "popup") {
        $widget = "clientParticipantEmbbed.html.twig";
      }
    
      $app->render($widget, [        
        "session" => $session,
        "participant" => $participation
      ]);
      
      if (isset($_SERVER['HTTP_REFERER'])) {
        $refered = Model::factory('refered')->where('session_id', $session->id)->where('url', $_SERVER['HTTP_REFERER'])->find_one();
        
        if (is_object($refered)) {
          $refered->views++;
        } else {
          $refered = Model::factory('refered')->create();
          $refered->session_id = $session->id;
          $refered->url = $_SERVER['HTTP_REFERER'];
          $refered->views = 1;
          $refered->requests = ($session->sessionLayout()->label == "embed-autostart"?1:0);
          $refered->calls = 0;
        }
        
        $refered->save();
      }
    }
  }
});

$app->get('/(:stoken)(/:ptoken)?', function($stoken, $ptoken = null) use ($app) {  

  global $_SERVER;

  $session = Model::factory('Session')->where('token',$stoken)->find_one();

  if (!is_object($session)) {
    
    $app->render('clientError.html.twig', [
      "message" => \Libs\Locale::getHtmlContent("embbed-session-not-found")      
    ]);
    
  } else {
    
    if ($ptoken) {
      $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
      
      if (!is_object($session)) {
        $participation = newParticipation($session);
      }
      
    } else {
      $participation = newParticipation($session);    
    }

    // Repeated from above, consider join code on a function
    $iceTurnApi = new IceTurnRestApi(\SiteConfig::getInstance()->get("stun_turn_rest_api_url"), \SiteConfig::getInstance()->get("stun_turn_rest_api_key"));      

    if ($session->sessionLayout()->label == "embed") {
      $widget = "clientParticipantPopupV2mini.html.twig";
    }

    if ($session->sessionLayout()->label == "popup") {
      $widget = "clientParticipantPopupV2.html.twig";
    }

    $app->render($widget, [        
      "session" => $session,
      "participant" => $participation,
      "meeting" => $participation->session()->activeMeeting(),
      "iceTurnApi" => $iceTurnApi
    ]);
    
    if (isset($_SERVER['HTTP_REFERER'])) {
      $refered = Model::factory('refered')->where('session_id', $session->id)->where('url', $_SERVER['HTTP_REFERER'])->find_one();
        
      if (is_object($refered)) {
        $refered->views++;
      } else {
        $refered = Model::factory('refered')->create();
        $refered->session_id = $session->id;
        $refered->url = $_SERVER['HTTP_REFERER'];
        $refered->views = 1;
        $refered->requests = 1;
        $refered->calls = 0;
      }
      
      $refered->save();
    }
  }
});
