<?php

$app->get('/ok', function() use ($app) {    
  $app->render(200, array("error" => false));
});

$app->get('/error', function() use ($app) {    
  $app->render(401, array("error" => true));
});
    
$app->get('/getVersion', function() use ($app) {  
  $result = array("version" => 1);  
  $app->render(200, array("result" => $result));
});

$app->get('/getClientSessionInfo/(:token)', function($token) use ($app) {  

  $session = Model::factory('Session')->where('token',$token)->find_one();

  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    $ss = Libs\SAMLSession::getInstance($session->mustAuthenticate);
  
    if (!$session->mustAuthenticate || $ss->isAuthenticated()) {
      $result = $session->public_data_array();      
      $result["messages"] = $session->getMessages();       
      $result["state"] = $session->getCurrentState(); 
      $result["meeting"] = $session->activeMeeting();
      $app->render(200, array("result" => $result));
    } else {
      $app->render(401, array("error" => true));
    }
  }

});

$app->get('/getClientSessionInfo/(:token)/field/(:field)', function($token, $field) use ($app) {  

  $session = Model::factory('Session')->where('token',$token)->find_one();

  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    $ss = Libs\SAMLSession::getInstance($session->mustAuthenticate);
  
    if (!$session->mustAuthenticate || $ss->isAuthenticated()) {
      $result = $session->public_data_array();            
      if (isset($result[$field])) {
        $app->render(200, array("result" => $result[$field]));
      } else {
        $app->render(404, array("error" => true));
      }
    } else {
      $app->render(401, array("error" => true));
    }
  }

});

$app->get('/getClientSessionInfo/(:token)/state', function($token) use ($app) {  

  $session = Model::factory('Session')->where('token',$token)->find_one();

  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    $ss = Libs\SAMLSession::getInstance($session->mustAuthenticate);
  
    if (!$session->mustAuthenticate || $ss->isAuthenticated()) {
      $result = $session->getCurrentState();      
      $app->render(200, array("result" => $result));
    } else {
      $app->render(401, array("error" => true));
    }
  }

});

$app->get('/getSessionInfo/(:token)', function($token) use ($app) {  

  $session = Model::factory('Session')->where('token',$token)->find_one();
    
  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    
    $ss = Libs\SAMLSession::getInstance(false);
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();  
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner...
      $app->render(200, array("result" => $session->as_array()));
    } else {
      $app->render(401, array("error" => true));
    }
  }

});

$app->get('/getSessionQueueInfo/(:token)/(:format)', function($token, $format) use ($app) {  

  $session = Model::factory('Session')->where('token',$token)->find_one();
    
  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    
    $ss = Libs\SAMLSession::getInstance(false);
    $user = Model::factory('User')->where('email',$ss->getEmail())->find_one();  
    
    if ($user->id == $session->owner_id) { // Trying to edit while not owner...

      $result = null;
    
      if ($format == "html") {
        $result = array("html" => "<b>HTML!</b> " . time());    
      }
      
      if ($format == "json") {
        $result = array("queueSize" => 0, "participants" => []);
      }
    
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }

});

$app->get('/getParticipantInfo/(:token)', function($token) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$token)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {
        
      $session = $participation->session();
              
      $result = $session->public_data_array();      
      //$result["messages"] = $session->getMessages();  
      
      $result["state"] = $participation->getCurrentStateLabel();
      $result["message"] = $participation->getCurrentMessage();
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});


$app->get('/getParticipantQueueInfo/(:ptoken)/html', function($ptoken) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {
        
      $session = $participation->session();
      $otherParticipants = $session->participationsWaiting();
      $result = array();
      $result["html"] = _("Participants waiting:") . count($otherParticipants);
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/requestToEnterQueue/(:ptoken)', function($ptoken) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $result = "";
      $participation->queueState_id  = Model::factory("QueueState")->where("label", "waiting-on-queue")->find_one()->id;
      $meeting = $participation->session()->activeMeeting();
      
      if ($meeting) {
        $participation->meeting_id = $meeting->id;
        $participation->registered = date("Y-m-d H:i:s");        
        $participation->save();
        appLog("enter-queue", $participation);
        $result = "ok";
      } else {
        $result = "unavailable";
      }
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/setParticipantFrienldyName/(:ptoken)', function($ptoken) use ($app) {  
   
  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
  
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $result = "";
      $data = json_decode($app->request()->getBody(), true);
      
      $participation->participantName = $data["friendlyName"];
      $participation->save();
      appLog("set-friendly-name", $participation, null, null, null, $app->request()->getBody());
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/rateMeeting/(:ptoken)', function($ptoken) use ($app) {  
   
  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
  
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $result = "";
      $data = json_decode($app->request()->getBody(), true);
      
      $participation->feedback = $data["rating"];
      $participation->save();
      appLog("rate-meeting", $participation, null, null, null, $app->request()->getBody());
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/requestToLeaveQueue/(:ptoken)/(:mode)', function($ptoken, $mode) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $participation->queueState_id  = Model::factory("QueueState")->where("label", "lurking")->find_one()->id;
      $participation->meeting_id = null;
      $participation->registered = null;
      $participation->save();
      appLog("leave-queue", $participation);
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/kick/(:stoken)/(:ptoken)', function($stoken, $ptoken) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if ($participation->session()->token == $stoken) {

      $participation->queueState_id  = Model::factory("QueueState")->where("label", "kicked")->find_one()->id;
      $participation->endTime = date("Y-m-d H:i:s");      
      $participation->save();
      appLog("kicked-queue", $participation);
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/call/(:stoken)/(:ptoken)', function($stoken, $ptoken) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if ($participation->session()->token == $stoken) {

      $participation->queueState_id  = Model::factory("QueueState")->where("label", "in-session")->find_one()->id;
      $participation->startTime = date("Y-m-d H:i:s");
      $participation->secondsWaited = strtotime($participation->startTime) - strtotime($participation->registered); // TO-DO
      
      $participation->save();
      appLog("in-session", $participation);
      $result = "ok";
      
      $app->render(200, array("result" => $result, "debug" => $participation->secondsWaited));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/requestHangup/(:ptoken)', function($ptoken) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $participation->queueState_id  = Model::factory("QueueState")->where("label", "disconnected")->find_one()->id;
      $participation->endTime = date("Y-m-d H:i:s");
      $participation->save();
      appLog("disconnected", $participation);
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->put('/participant/(:ptoken)/register/(:peerjsid)', function($ptoken, $peerjsid) use ($app) {  

  $participation = Model::factory('Participation')->where('token',$ptoken)->find_one();
    
  if (!is_object($participation)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $participation->peerJSId =$peerjsid;
      $participation->save();
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});

$app->get('/meeting/(:mtoken)/logEvent/(:event)/(:type)', function($mtoken, $event, $type) use ($app) {  
  $meeting = Model::factory('Meeting')->where('token',$mtoken)->find_one();
  if (!is_object($meeting)) {
    $app->render(404, array("error" => true));
  } else {
    appLog("meeting-error", $meeting, null, null, null, "meeting/" . $event . "/" . $type);
    $app->render(200, array("result" => true));
  }
});

$app->get('/session/(:stoken)/logEvent/(:event)/(:type)', function($stoken, $event, $type) use ($app) {  
  $session = Model::factory('Session')->where('token',$stoken)->find_one();
  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else {
    $meeting = $session->activeMeeting();
    appLog("session-error", $session, null, $meeting->id, null, "session/" . $event . "/" . $type);
    $app->render(200, array("result" => true));
  }
});

$app->get('/meeting/(:mtoken)/(:queueAction)', function($mtoken, $queueAction) use ($app) {  
  $meeting = Model::factory('Meeting')->where('token',$mtoken)->find_one();
  if (!is_object($meeting)) {
    $app->render(404, array("error" => true));
  } else {    
  
    if ($queueAction != "queueStatus") {
  
      if ($queueAction == "queueOpen") {
        $meetingState = Model::factory('MeetingState')->where('label','waiting')->find_one();
        $meeting->meetingState_id = $meetingState->id;      
      }
      
      if ($queueAction == "queueClose") {
        $meetingState = Model::factory('MeetingState')->where('label','queue-closed')->find_one();      
        $meeting->meetingState_id = $meetingState->id;
      }
    
      $meeting->save();
    } else {
      $meetingState = $meeting->MeetingState();
    }
    
    $result["status"] = $meetingState->label;
    $result["participants-waiting"] = count($meeting->Session()->participationsWaiting());
    $app->render(200, array("result" => $result));
  }
});


$app->put('/meeting/(:mtoken)/register/(:peerjsid)', function($mtoken, $peerjsid) use ($app) {  

  $meeting = Model::factory('Meeting')->where('token',$mtoken)->find_one();
    
  if (!is_object($meeting)) {
    $app->render(404, array("error" => true));
  } else {
        
    if (true) {

      $meeting->peerJSId = $peerjsid;
      $meeting->save();
      
      $result = "ok";
      
      $app->render(200, array("result" => $result));

    } else {
      $app->render(401, array("error" => true));
    }
  }
});


$app->get('/mail/test', function() use ($app) {  
  
  $mail = new WebMailer();
      
	$mail->Subject = "WebTUT: Test!";
	
  $message = "RUI";
  
 	$test_message = \Libs\Locale::processFile("mail_test",
		array("{message}" => print_r($message, true)				  
		));;
	
	$mail->ConstructBrandedMessage($test_message);
	$mail->SendToAdmin();	

  $app->render(200, array("result" => 0));
});

$app->get('/notify/(:stoken)/(:destination)', function($stoken, $destination) use ($app) {  

  $result = 10;
  
  $session = Model::factory('Session')->where('token',$stoken)->find_one();

  if (!is_object($session)) {
    $app->render(404, array("error" => true));
  } else 
  if (!$session->notifyHost) {    
    $result = 1;
  } else   
  if ($destination == "host") {
          
    $owner = $session->owner()->find_one();      
    
    $queue = $session->participationsWaiting();
    
    $cnt = 0;
    foreach($queue as $participant) {
      $cnt++;
    }
    
    $participantsTable = "<p>" . _("No participants waiting.") . "</p>";
    
    if ($cnt > 0) {
      $participantsTable = "<table width='100%'>";
      $participantsTable .= "<tr><th><font size=\"2\" face=\"Arial\">" . _("Name") . "</font></th>" .
                              "<th><font size=\"2\" face=\"Arial\">" . _("Email") . "</font></th>" .
                              "<th><font size=\"2\" face=\"Arial\">" . _("Start Time") . "</font></th>" .
                            "</tr>";
      foreach($queue as $participant) {
        $participantsTable .= "<tr>" .
                                "<td align=\"center\"><font size=\"2\" face=\"Arial\">" . $participant->participantName . "</td>" .
                                "<td align=\"center\"><font size=\"2\" face=\"Arial\">" . $participant->participantEmail . "</td>" .
                                "<td align=\"center\"><font size=\"2\" face=\"Arial\">" . $participant->startTime . "</td>" .
                              "</tr>";      
      }
      $participantsTable .= "</table>";            
    }
    
    $mail_prefix = \SiteConfig::getInstance()->get("email_subject_prefix");
  
  
    //$mail = new ThrottleWebMailer();
    $mail = new WebMailer();
    
    $mail->Subject = $mail_prefix . $session->title;
      
    $notify_message = \Libs\Locale::processFile("mail_students_waiting",
      array("{participantsTable}" => $participantsTable,
            "{session_host_email}" => $owner->email,
            "{session_host_name}" => $owner->name,
            "{session_name}" => $session->title,
            "{session_url_dashboard}" => \SiteConfig::getInstance()->get("full_url") . "/tut/dashboard/" . $stoken,
            "{session_url_enter}" => \SiteConfig::getInstance()->get("full_url") . "/tut/enter/" . $stoken,
           )
    );

    $mail->ConstructBrandedMessage($notify_message);
    $mail->addAddress($owner->email);
    $mail->send();
    $result = 0;
  }

  $app->render(200, array("result" => $result));
});

