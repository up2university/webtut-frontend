<?php
         
function AppLog($label, $object, $sessionId = null, $meetingId = null, $participationId = null, $param = null)
{
    if (is_object($object)) {
      $objectClassName = get_class($object);
    } else {
      $objectClassName = "Null";
    }
    $log = Model::factory('AppLog')->create();
    $log->label = $label;
    $log->timestamp = date( 'Y-m-d H:i:s', time() );
    $log->logEntityType_id = Model::factory('LogEntityType')->where("label", $objectClassName)->find_one()->get("id");
    
    if ($objectClassName != "Null") {
      $log->entity = json_encode($object->as_array());
      $log->entity_id = $object->id;
    }
    
    $log->session_id = $sessionId;
    $log->meeting_id = $meetingId;
    $log->participation_id = $participationId;
    
    $session = Libs\SAMLSession::getInstance(false);
    if ($session->isAuthenticated()) {      
      $log->user_id = $session->getUser()->id;
    } else {
      $log->user_id = null;
    }
    
    switch ($objectClassName) {
      case "Meeting" : 
          $log->meeting_id = $object->id;
          $log->session_id = $object->session_id;
        break;
      case "Session" : 
          $log->session_id = $object->id;
        break;
      case "Partiicipation" : 
          $log->participation_id = $object->id;      
          $log->meeting_id = $object->meeting_id;
          $log->session_id = $object->meeting()->session_id;
          $log->user_id = $object->user_id;
        break;
      default:
    }
    
    $log->param = $param;
    $log->save();

}

