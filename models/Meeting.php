<?php

class Meeting extends Model {
  public static $_table = 'meeting';
  public static $_id_column = 'id';
  
  public function session() {
    return $this->belongs_to('Session')->find_one();
  }  

  public function meetingState() {
    return $this->belongs_to('MeetingState')->find_one();
  }  
 
  public function participants() {
    return $this->has_many('Participation');
  }  
 
  public function InSessionParticipant() 
  {   
    $state_id = Model::factory("QueueState")->where('label','in-session')->find_one()->id;
    return $this->participants()->where("queueState_id", $state_id)->find_one();
  }    
}

