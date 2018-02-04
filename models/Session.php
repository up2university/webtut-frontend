<?php

// Define the custom sort function
function messageOrder($a,$b) {
  return $a["order"]>$b["order"];
}
     
class Session extends Model
{
  public static $_table = 'session';
  public static $_id_column = 'id';
  
  private static $_state = null;
  private static $_stateExtraInfo = null;
  
  function getFormFields()
  {
    return array( "id"                      => array("type" => "integer",               "default" => 0,         "public" => false),
                  "title"                   => array("type" => "string",                "default" => "",        "public" => true), 
                  "description"             => array("type" => "string",                "default" => "",        "public" => true), 
                  "dateStart"               => array("type" => "date",                  "default" => time(),    "public" => true), 
                  "dateEnd"                 => array("type" => "date",                  "default" => time() + 3600 * 24 * 30 * 6, "public" => true),
                  "maxQueue"                => array("type" => "integer",               "default" => 5,         "public" => false),
                  "mustAuthenticate"        => array("type" => "boolean",               "default" => false,     "public" => false),
                  "visibleToAll"            => array("type" => "boolean",               "default" => false,     "public" => false),
                  "active"                  => array("type" => "boolean",               "default" => true,      "public" => true),
                  "activeMonday"            => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeTuesday"           => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeWednesday"         => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeThursday"          => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeFriday"            => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeSaturday"          => array("type" => "boolean",               "default" => false,     "public" => false),
                  "activeSunday"            => array("type" => "boolean",               "default" => false,     "public" => false),
                  "repeatMonday"            => array("type" => "time",                  "default" => 3600 * 9,  "public" => false), 
                  "repeatTuesday"           => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "repeatWednesday"         => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "repeatThursday"          => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "repeatFriday"            => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "repeatSaturday"          => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "repeatSunday"            => array("type" => "time",                  "default" => 3600 * 9,  "public" => false),
                  "duration"                => array("type" => "time",                  "default" => 3600 * 2,  "public" => false),
                  "frameWidth"              => array("type" => "integer",               "default" => 250,       "public" => true),
                  "frameHeight"             => array("type" => "integer",               "default" => 300,       "public" => true),
                  "owner_id"                => array("type" => "integer",               "default" => 0,         "public" => false),
                  "notifyHost"              => array("type" => "boolean",               "default" => true,      "public" => false),
                  "sessionQueueStrategy_id" => array("type" => "sessionQueueStrategy",  "default" => 0,         "public" => false),
                  "createdAt"               => array("type" => "time",                  "default" => 0,         "public" => false), 
                  "token"                   => array("type" => "string",                "default" => md5(time() . "+" . \SiteConfig::getInstance()->get("token_hash")), "public" => true),
                  "sessionLayout_id"        => array("type" => "integer",               "default" => 0,         "public" => false), 
                  "cssurl"                  => array("type" => "string",                "default" => \SiteConfig::getInstance()->get("default_css_url"),        "public" => false),
                  );
  }
    
  public function owner() {
    return $this->belongs_to('user', 'owner_id');
  }

  public function messages() {
    return $this->has_many('sessionMessage');
  }

  public function message($label) {
    $sessionMessageType = Model::factory("SessionMessageType")->where("label", $label)->find_one();
    
    if (is_object($sessionMessageType)) {
      $message = $this->messages()->where("sessionMessageType_id", $sessionMessageType->id)->find_one();
      if (is_object($message)) {
        return $message->message;
      } else {
        return \Libs\Locale::getFileContent("messages-" . $label); // Returns default text
      }
    }
          
    AppLog("unknown-message", $this, null, null, null, "LABEL: '$label'");
    
    return null;
  }


  public function meetings() {
    return $this->has_many('Meeting');
  }

  public function participations() {    
    return $this->has_many('Participation');
  }
  
  public function meetingStates()
  {
    return Model::factory("MeetingState");
  }
  
  public function sessionLayouts()
  {
    return Model::factory("sessionLayout");
  }
  
  public function participationsWaiting() {    
    return $this->participations()->inner_join("queueState", array("queueState_id", "=", "queueState.id"))->where("queueState.waiting", 1)->find_many();
  }
  
  public function sessionPossibleQueueStrategies() {    
    return Model::factory("SessionQueueStrategy")->find_many();
  }

  public function sessionPossibleLayouts() {    
    return Model::factory("sessionLayout")->find_many();
  }
  
  
  public function public_data_array() {
    $data = $this->as_array();
    $formFields = $this->getFormFields();
    foreach($data as $key => $value) {                 
      if (!$formFields[$key]["public"]) {
        unset($data[$key]);
      }
    }
    
    return $data;
  }  

  function getCurrentMessage()
  {      
    $label = $this->getCurrentState();    
    $currentMessage = $this->message($label);
    
    if ($currentMessage == null) {    
      $currentMessage = \Libs\Locale::getFileContent("messages-" . $label);
    }
    
    return $currentMessage;
  }
     
     
  function getMessages()
  {
    $m = array();
    $sessionMessageTypes = Model::factory('sessionMessageType')->find_many();

    foreach($sessionMessageTypes as $sessionMessage) {
      $a = $sessionMessage->as_array();
      $a["name"] = _($a["name"]);
      $a["message"] = \Libs\Locale::getFileContent("messages-" . $a["label"]); // Get default message content      
      $m[$a["id"]] = $a;        
    }
    
    $messages = $this->messages()->find_many();
    foreach($messages as $key => $message) {
      $m[$message->sessionMessageType_id]["message"] = $message->message;        
    }    

    usort($m, "messageOrder");
    
    return $m;
  }
 
  function getActiveWeekdays()
  {
    $w = $weekdays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    $nw = [];
    foreach($weekdays as $day) {
      if ($this->get("active" . $day)) {
        $nw[] = $day;
      }
      
    }
    if (count($nw) == 0) {
      $nw = $w;
    }

    return $nw;
  }

  function activeMeetings()
  {    
    $meetings = $this->meetings();
    
    if ($meetings != null) {
      $meetings = $meetings->where('endTime', '0000-00-00 00:00:00')->find_many();
      if ($meetings != false) {
        return $meetings;
      }
    }
    return null;    
  }

  function activeMeeting()
  {    
    $meetings = $this->meetings();
    
    if ($meetings != null) {
      $meeting = $meetings->where('endTime', '0000-00-00 00:00:00')->find_one();
      if ($meeting != false) {
        return $meeting;
      }
    }
    return null;    
  }
  
  function setCurrentState($newState, $extraInfo)
  {
    $this->_state = $newState;
    $this->_stateExtraInfo = $extraInfo;
    return $newState;
  }
  
  function getCurrentStateExtraInfo()
  {
    if (!$this->_state)
      $this->getCurrentState();
    return $this->_stateExtraInfo;
  }
  
  function getCurrentState()
  {    
    if ($this->_state)
      return $this->_state;
    
    if (!$this->active) {
      return $this->setCurrentState("closed", _("Disabled by owner."));
    }

    $activeMeeting = $this->activeMeeting();    
    
    if ($activeMeeting) {
      return $activeMeeting->meetingState()->label;      
    } else {
      
      $timestamp = time();      
      $date = date("Y-m-d", $timestamp);
      $time = date("H:m:i", $timestamp);
      $dayOfWeek = date('l', $timestamp);
 
      if (($date >= $this->dateStart) && ($date <= $this->dateEnd)) {

        $weekdays = $this->getActiveWeekdays();

        if (count($weekdays) == 7) {          
          return $this->setCurrentState("deserted", _("No expert available at the moment."));
        }
        
        if (in_array($dayOfWeek, $weekdays)) {
          
          $secondsToday = strtotime($time);
          $beginToday = strtotime( $this->get("repeat" . $dayOfWeek));
          $duration   = strtotime( $this->get("duration"));          
          $endToday   = $beginToday + $duration;
          
          if (($secondsToday > $beginToday) && ($secondsToday < $endToday)) {
            return $this->setCurrentState("deserted", _("No expert available at the moment."));
          } else {
            return $this->setCurrentState("closed", _("The session isn't available at the current moment."));            
            // return "closed (not within time range)" . date("H:m:i", $beginToday) . " < " . date("H:m:i", $secondsToday) . " < " . date("H:m:i", $endToday);
          }
        } else {
          return $this->setCurrentState("closed", _("The session isn't available today."));                      
        }
      } else {
        return $this->setCurrentState("closed", _("The session on the current date."));
        // return "closed (not within date range)" . $this->dateStart . $time;
      }
    }    
  
    return $this->setCurrentState("undefined", _("Unknown session state.")); // This should never return!
  }
  
  public function sessionLayout() {
    return $this->belongs_to('SessionLayout')->find_one();
  }  
}
