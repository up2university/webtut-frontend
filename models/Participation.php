<?php

class Participation extends Model {
  public static $_table = 'participation';
  public static $_id_column = 'id';
  
  public function meeting() {
    return $this->belongs_to('Meeting')->find_one();
  }  
  
  public function session() {    
    return $this->belongs_to("Session")->find_one();
  }  

  public function queueState() {
    return $this->belongs_to('QueueState')->find_one();
  }  

  public function getCurrentMessage() {  
    return $this->session()->message("session-" . $this->queueState()->label);
  }

  public function getSecondsWaited() {
    if ($this->registered) {
      return strtotime(date("Y-m-d H:i:s")) - strtotime($this->registered) - 3600; // TODO: This 3600 is a TimeZone shift hardcoded. Some other solution must be implemented.
    } else {
      return 0;
    }
  }
  
  public function getCurrentStateLabel() {  
  
    $label = Model::factory("QueueState")->where('id', $this->queueState_id)->find_one()->label;
        
    if ($label == "lurker") {
      $label = $this->session()->getCurrentState();
    }
    
    return $label;
  }
}
