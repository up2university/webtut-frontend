<?php

$app->get('/:reference', function ($reference) use ($app){
  
  $ok = preg_match("/[a-zA-Z0-9\-]+/", $reference);
  
  if (($ok == 1) && (\Libs\Locale::existsHtmlContent("doc/" . $reference)))
    $app->render('doc.html.twig', [
      'content' => \Libs\Locale::getHtmlContent("doc/" . $reference)]);
  else
    $app->redirect(\SiteConfig::getInstance()->get("base_path") . "/");
        
});