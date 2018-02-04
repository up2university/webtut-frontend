<?php

$app->get('/', function () use ($app) {
        
    $ss = Libs\SAMLSession::getInstance();
        
    if ($ss->isAdmin()) {
      
      $sql = 'select t.label tlabel, l.label llabel, count(*) cnt from appLog l, logEntityType t where t.id = l.logEntityType_id group by t.label, l.label';
    
      $s = array();
      $statistics = ORM::for_table('AppLog')
        ->raw_query($sql)
        ->find_many();
     
      foreach ($statistics as $stat) {
        $s[$stat["tlabel"]][$stat["llabel"]] = $stat["cnt"];
      }
      
      $app->render('admin.html.twig', [
        "global_statistics" => $s
      ]);        
    } else {
      $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/profile/me");    
    }

});

