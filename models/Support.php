<?php

class SessionLayout extends Model {
  public static $_table = 'sessionLayout';
  public static $_id_column = 'id';
}

class SessionQueueStrategy extends Model {
  public static $_table = 'sessionQueueStrategy';
  public static $_id_column = 'id';
}

class QueueState extends Model {
  public static $_table = 'queueState';
  public static $_id_column = 'id';
}

class MeetingState extends Model {
  public static $_table = 'meetingState';
  public static $_id_column = 'id';
}

class AppLog extends Model {
  public static $_table = 'appLog';
  public static $_id_column = 'id';
  
  public function logEntityType() {
    return $this->has_one('LogEntityType');
  }  
}

class LogEntityType extends Model {
  public static $_table = 'logEntityType';
  public static $_id_column = 'id';
}

class SessionMessage extends Model {
  public static $_table = 'sessionMessage';
  public static $_id_column = 'id';  
}

class SessionMessageType extends Model {
  public static $_table = 'sessionMessageType';
  public static $_id_column = 'id';  
}

class User extends Model {
  public static $_table = 'user';
  public static $_id_column = 'id';

  public function sessions() {    
    return $this->has_many('Session', 'owner_id');
  }

}

class refered extends Model {
  public static $_table = 'refered';
  public static $_id_column = 'id';
}

