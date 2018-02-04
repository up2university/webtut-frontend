<?php

function editForm($token, $app)
{
  $ss = Libs\SAMLSession::getInstance();
  $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
  
  $formData = array("token" => 0);
  $mode = "new";
   
  if ($token != "0") {
    
    $session = Model::factory('Session')->where('token',$token)->find_one();
    
    if (! $session instanceof  Session) {
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");
    }
    
    if ($user->id != $session->owner_id) { // Trying to edit while not owner...
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");          
    }
    
    $formData["id"] = $session->id;
    foreach($session->getFormFields() as $formField => $attributes) {
      $formData[$formField] = $session->$formField;      
    }
      
    $mode = "edit";      
  } else {   
    $session = Model::factory('Session')->create();
    
    $formData["id"] = 0;
    foreach($session->getFormFields() as $formField => $attributes) {
      $formData[$formField] = $attributes["default"];
    }    
    
    $formData["sessionQueueStrategy_id"] = Model::factory('sessionQueueStrategy')->where("defaultStrategy",1)->find_one()->id;
  }     
  
  $formData["sessionPossibleQueueStrategies"] = $session->sessionPossibleQueueStrategies();
  
  $app->render('edit.html.twig', [
    'mode' => $mode,
    'tut' => $formData,
  ]);
};

$app->get('/new/', function () use ($app)
{
  editForm(0, $app);
});

$app->get('/edit/', function () use ($app)
{
  editForm(0, $app);
});

$app->get('/edit/(:token)', function ($token) use ($app)
{
  editForm($token, $app);
});


$app->post('/edit/(:token)/(:form)', function ($token, $form) use ($app)
{
    $req = $app->request();
    $ss = Libs\SAMLSession::getInstance();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();

    $mode = "update";
    
    if ($req->post("id") == "0") { // Create
      $mode = "create";
      $session = Model::factory('Session')->create();
    } else { // Update
      $session = Model::factory('Session')->where('token',$token)->find_one();

      if ($user->id != $session->owner_id) { // Trying to edit while not owner...   
        $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/me");    
      }
    }
        
    foreach($session->getFormFields() as $formField => $attributes) {
      if (($attributes["type"] == "boolean") && ($form == "details")) {
        $session->$formField = ($req->post($formField)==null?0:1);
      } else 
      if ($req->post($formField)!= null) {
        $session->$formField = $req->post($formField);
      } else 
      if (!isset($session->$formField)) {
        $session->$formField = $attributes["default"];
      }
    }       
    
    $session->owner_id = $user->id;
    $session->save();
    $token = $session->token;
    AppLog($mode, $session);
    
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/dashboard/$token");    
});


$app->get('/dashboard/(:token)', function ($token) use ($app){

    $ss = Libs\SAMLSession::getInstance();
    $session = Model::factory('Session')->where('token',$token)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 
    
      $app->render('dashboard.html.twig', [        
        "session" => $session,        
      ]);
      
    } else { // Not Authorized
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
    }
    
});

$app->get('/my/', function () use ($app){

    $ss = Libs\SAMLSession::getInstance();
    $sessions = Model::factory('User')->where('email', $ss->getEmail())->find_one()->sessions()->find_many();
    
    $app->render('list.html.twig', [
      "title" => _("My TUT List"),
      "text" => \Libs\Locale::getHtmlContent("my-tut-list"),
      "sessions" => $sessions,
    ]);
    
});


$app->get('/all/', function () use ($app) {

    $ss = Libs\SAMLSession::getInstance(false);
    $sessions = Model::factory('Session')->where('active', 1)->where('visibleToAll', 1)->find_many();
    
    $app->render('publicList.html.twig', [
      "title" => _("All TUTs available"),
      "text" => \Libs\Locale::getHtmlContent("all-tut-list"),
      "sessions" => $sessions,
      "ss" => $ss
    ]);
    
});

$app->get('/delete/(:token)', function ($token) use ($app) {

    $ss = Libs\SAMLSession::getInstance();
    $session = Model::factory('Session')->where('token',$token)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();

    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 
      AppLog("delete", $session);
      $session->delete();
    }
 
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
});

$app->get('/enter/(:stoken)', function ($stoken) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();
    $session = Model::factory('Session')->where('token',$stoken)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    // Trying to edit while owner?
    if ($user->id == $session->owner_id) { // Yes!
      
      $meeting = $session->activeMeeting();
      
      if ($meeting == null) {
        $meeting = Model::factory('Meeting')->create();
        $meeting->session_id = $session->id;
        $meeting->expert_id = $user->id;
        $meeting->expert_name = $ss->getFriendlyName();
        $meeting->startTime = 0;
        $meeting->endTime = 0;
        $meeting->meetingState_id = Model::factory('MeetingState')->where('label','opened')->find_one()->id;
      
        $meeting->token = md5(time() . "+" . \SiteConfig::getInstance()->get("token_hash"));
        $meeting->save();
        AppLog("create", $meeting); 
      } else {
        AppLog("reusing meeting", $meeting); 
      }
      
      $iceTurnApi = new IceTurnRestApi(\SiteConfig::getInstance()->get("stun_turn_rest_api_url"), \SiteConfig::getInstance()->get("stun_turn_rest_api_key"));      

      $app->render('room.html.twig', [        
        "session" => $session,
        "meeting" => $meeting,
        "user" => $user,
        "iceTurnApi" => $iceTurnApi
      ]);
      
    } else { // Not Authorized
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
    }

});

$app->put('/meeting/(:mtoken)/queueclose', function ($mtoken) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();
    $meeting = Model::factory('Meeting')->where('token', $mtoken)->find_one();
    $session = $meeting->session();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();    
    
    if ( ($user->id == $session->owner_id) || ($user->id == $meeting->expert_id) ) { // Trying to edit while not owner...     
      $meeting->meetingState_id = Model::factory('MeetingState')->where('label','queue-closed')->find_one()->id;      
      $meeting->save();
      AppLog("queue-close", $meeting, $session->id);             
      
      $app->response->write('ok');
    } else {
      $app->response->write('error');
    }    
    
});

$app->put('/meeting/(:mtoken)/queueopen', function ($mtoken) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();
    $meeting = Model::factory('Meeting')->where('token', $mtoken)->find_one();
    $session = $meeting->session();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();    
    
    if ( ($user->id == $session->owner_id) || ($user->id == $meeting->expert_id) ) { // Trying to edit while not owner...       
      $meeting->meetingState_id = Model::factory('MeetingState')->where('label','waiting')->find_one()->id;      
      $meeting->save();
      AppLog("queue-open", $meeting, $session->id);             
      
      $app->response->write('ok');
    } else {
      $app->response->write('error');
    }    
    
});


$app->put('/meeting/wait/(:mtoken)', function ($mtoken) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();
    $meeting = Model::factory('Meeting')->where('token', $mtoken)->find_one();
    $session = $meeting->session();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 
      $meeting->startTime = date("Y-m-d H:i:s");
      $meeting->meetingState_id = Model::factory('MeetingState')->where('label','waiting')->find_one()->id;      
      $meeting->save();
      AppLog("waiting", $meeting, $session->id);
      $app->response->write('ok');
    } else {
      $app->response->write('error');
    }    
});

$app->put('/meeting/close/(:mtoken)/(:mode)', function ($mtoken, $mode) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();
    $meeting = Model::factory('Meeting')->where('token', $mtoken)->find_one();
    $session = $meeting->session();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 
      AppLog("pre-close", $meeting);
      if ($mode == "disconnect")
        $meeting->endTime = date("Y-m-d H:i:s");
      
      $meeting->meetingState_id = Model::factory('MeetingState')->where('label','closed')->find_one()->id;      
      $meeting->save();
      AppLog("close", $meeting);
      
      if ($mode == "disconnect") {
        $state_id = Model::factory("QueueState")->where('label','disconnected')->find_one()->id;
        foreach($meeting->participations() as $participation) {
           $participation->queueState_id = $state_id;
           $participation->endTime = date("Y-m-d H:i:s");
           $participation->save();
           AppLog("disconnect", $participation);
        }
      }
            
      $app->response->write('ok');
    } else {
      $app->response->write('error');
    }    
});



$app->get('/statistics/(:token)', function ($token) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();  
    $session = Model::factory('Session')->where('token',$token)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 

      $sql = 'select t.label tlabel, l.label llabel, count(*) cnt from appLog l, logEntityType t where t.id = l.logEntityType_id and l.session_id = ' . $session->id . ' group by t.label, l.label';
    
      $s = array();
      $statistics = ORM::for_table('AppLog')
        ->raw_query($sql)
        ->find_many();
     
      $sql = "select count(*) as meetings, avg(feedback) as feedback, avg(secondsWaited) as waited from participation where feedback and session_id = " . $session->id;
      $qrow = ORM::for_table('participation')->raw_query($sql)->find_one();
      $s["feedback-average"] = $qrow->feedback;
      $s["feedback-meetings"] = $qrow->meetings;
      $s["waited-average"] = $qrow->waited;
     
      foreach ($statistics as $stat) {
        $s[$stat["tlabel"]][$stat["llabel"]] = $stat["cnt"];
      }      

      $app->render('statistics.html.twig', [        
        "session" => $session->as_array(),
        "statistics" => $s
      ]);
      
    } else { // Not Authorized
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
    }

});

$app->get('/messages/(:token)', function ($token) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();  
    $session = Model::factory('Session')->where('token',$token)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 

      $app->render('messages.html.twig', [
        "session" => $session->as_array(),
        "messages" => $session->getMessages(),
      ]);
      
    } else { // Not Authorized
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
    }

});

$app->post('/messages/(:token)', function ($token) use ($app) {
  
  $req = $app->request();
  $ss = Libs\SAMLSession::getInstance();  
  $session = Model::factory('Session')->where('token',$token)->find_one();
  $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
  if ($user->id == $session->owner_id) { // Trying to edit while not owner... 
    
    $sessionMessageTypes = Model::factory('sessionMessageType')->find_many();

    $formData["id"] = $session->id;
    foreach($sessionMessageTypes as $sessionMessageType) {
      $message = Model::factory('SessionMessage')->where('session_id', $session->id)->where('sessionMessageType_id', $sessionMessageType->id)->find_one();
      if (!is_object($message))
        $message = Model::factory('SessionMessage')->create();
      $message->sessionMessageType_id = $sessionMessageType->id;
      $message->session_id = $session->id;      
      $message->message = $req->post("msg-" . $sessionMessageType->label);
      $message->save();
    }    
     
    AppLog("message-update", $session);
    
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/dashboard/$token");
    
  } else { // Not Authorized
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
  };  
  
});

$app->get('/embbed/(:token)', function ($token) use ($app)
{
    $ss = Libs\SAMLSession::getInstance();  
    $session = Model::factory('Session')->where('token',$token)->find_one();
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner... 

      $formdata = $session->as_array();
      $formdata["sessionPossibleLayouts"] = $session->sessionPossibleLayouts();
    
      $html = array(
                 "html" => "<a href='" . \SiteConfig::getInstance()->get("full_url") . "/embbed/$token" . "'></a>",
                 "script" => "<script src='" . \SiteConfig::getInstance()->get("full_url") . "/embbed/$token/js" . "'></script>",
                 "testURL" => \SiteConfig::getInstance()->get("full_url") . "/embbed/$token/test");
    
      $app->render('embbed.html.twig', [
        "session" => $formdata,
        "embbedHtml" => $html
      ]);
      
    } else { // Not Authorized
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/my");    
    }

});

$app->post('/embbed/(:token)', function ($token) use ($app)
{
  $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/tut/dashboard/$token");
});

$app->get('/getSessionExperts/(:token)/(:format)', function($token, $format) use ($app) {  

  //    var_dump(Model::factory('Session')->where('token',$token)->find_one()->meetings()->find_many());
  $session = Model::factory('Session')->where('token',$token)->find_one();
  
  if ($session) {
      
    $expertMeetings = $session->activeMeetings();
    
    $html = $app->view()->render('dashboardExpertTable.html.twig', [
      "session" => $session,
      "meetings" => $expertMeetings
    ]);  
  } else {
    $html = _("unknown session:") . $token;
  }
  
  $app->response->headers->set('Content-Type', 'application/json');
  $app->response->write(json_encode(array("result" => array ("html" => $html))));
});

$app->get('/getSessionQueue/(:token)/(:format)', function($token, $format) use ($app) {  
  
  $session = Model::factory('Session')->where('token',$token)->find_one();
  
  if ($session) {

    $queue = $session->participationsWaiting();
    if ($format == "html") {
      $html = $app->view()->render('dashboardParticipationsTable.html.twig', [
        "session" => $session,
        "queue" => $queue
      ]);  
    } else {
      $html = $app->view()->render('dashboardParticipationsTableLight.html.twig', [
        "session" => $session,
        "queue" => $queue
      ]);  
    }
  } else {
    $html = "unknown session: $token";      
  }
  
  $app->response->headers->set('Content-Type', 'application/json');
  $app->response->write(json_encode(array("result" => array ("html" => $html))));
});

$app->get('/getInSessionInfo/(:token)/(:format)', function($token, $format) use ($app) {  
  
  $session = Model::factory('Session')->where('token',$token)->find_one();
  
  if ($session) {

    $meeting = $session->activeMeeting();
    
    if ($meeting) {
      $participant = $meeting->InSessionParticipant();      
      if ($format == "html") {
        $html = $app->view()->render('inSessionInfo.html.twig', [
          "session" => $session,
          "meeting" => $meeting,
          "participant" => $participant,          
        ]);  
      } else {
        $html = $app->view()->render('inSessionInfoLight.html.twig', [
          "session" => $session,
          "meeting" => $meeting,
          "participant" => $participant,          
        ]);  
      }
    } else {
      $html = "no active meeting!";      
    }
  } else {
    $html = "unknown session: $token";      
  }
  
  $app->response->headers->set('Content-Type', 'application/json');
  $app->response->write(json_encode(array("result" => array ("html" => $html))));
});

$app->get('/vcalendar/(:stoken)/(:type)', function($stoken, $type) use ($app) {  
  
  $session = Model::factory('Session')->where('token',$stoken)->find_one();
  
  if ($session) {
  
    $description = "";
    $url = "";
    
    $ss = Libs\SAMLSession::getInstance(false);
    if ($ss->isAuthenticated()) {
      $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();    
      if ($user->id == $session->owner_id) { // Owner?
        if ($type == "expert") {
           $description = "";
           $url = "";
        }          
      }
    }
  
    $vcalendar = new Sabre\VObject\Component\VCalendar([
      'VEVENT' => [
        'SUMMARY' => $session->title,
        'CATEGORIES' => 'MEETING',
        'DTSTART' => new \DateTime($session->dateStart),
        'DTEND'   => new \DateTime($session->dateEnd),
        'DESCRIPTION' => $description,
        'LOCATION' => \SiteConfig::getInstance()->get("app_name"),
        'URL' => $url
      ],
      'VALARM' => [
        'TRIGGER' => 0
      ]
    ]);

    $app->response->headers->set('Content-Type', 'text/calendar');
    $app->response->write($vcalendar->serialize());         
  } else {
    $app->halt("");
  }    
});

$app->get('/client/(:token)', function ($token) use ($app) {
      $app->render('doc.html.twig', [        
        "content" => "/client/:token",
      ]);
});

$app->get('/client/(:token)/qrcode', function ($token) use ($app) {
      $app->render('doc.html.twig', [        
        "content" => "/client/:token/qrcode",
      ]);
});
