<?php

namespace Libs;

class Locale extends \Slim\Middleware
{
  
  static public function getLocaleFromLabel($label) {
 
    foreach(\SiteConfig::getInstance()->get("locales") as $locale) {
      if (strtoupper($label) == strtoupper($locale["label"])) {
        return $locale["locale"];
      }
    }
  
    return \SiteConfig::getInstance()->get("defaultLocale");
  }

  static public function getLabelFromLocale($localeX) {
    
    foreach(\SiteConfig::getInstance()->get("locales") as $locale) {
      if (strtoupper($localeX) == strtoupper($locale["locale"])) {
        return $locale["label"];
      }
    }
    
    return self::getLabelFromLocale(\SiteConfig::getInstance()->get("defaultLocaleLabel"));
  }
  
  public function call()
  {    
    $app = \Slim\Slim::getInstance();    
    $current_lang = $this::getCurrentLang();
            
    // Set language to Current Language
    putenv('LANG=' . $current_lang . ".utf8");
    setlocale(LC_MESSAGES, $current_lang);

    // Specify the location of the translation tables
    bindtextdomain(\SiteConfig::getInstance()->get("locale-textdomain"), \SiteConfig::getInstance()->get("install_path") . "/" . \SiteConfig::getInstance()->get("locale-path"));
    bind_textdomain_codeset(\SiteConfig::getInstance()->get("locale-textdomain"), 'UTF-8');

    // Choose domain
    textdomain(\SiteConfig::getInstance()->get("locale-textdomain"));

    $app->view()->set('lang', array("label" => $this::getLabelFromLocale($current_lang),
                                    "locale" => $current_lang,
                                    "obj" => $this));

    $this->next->call();
  }
  
  static public function getCurrentLang()
  {
    $app = \Slim\Slim::getInstance();    
    $current_lang = $app->getCookie(\SiteConfig::getInstance()->get("locale-cookie-name"));
    
    if ($current_lang == "") {

      $defaultLocaleLabel = null;
      if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $labelFromHTTP = strtoupper(substr(locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2));

        foreach(\SiteConfig::getInstance()->get("locales") as $locale) {
          if ($labelFromHTTP == $locale["label"]) {
            $defaultLocaleLabel = $labelFromHTTP;
          }
        }
      } 

      if ($defaultLocaleLabel == null) {
        $defaultLocaleLabel = \SiteConfig::getInstance()->get("defaultLocaleLabel");
      }
      $current_lang = Locale::getLocaleFromLabel($defaultLocaleLabel);      
    }
    
    return $current_lang;
  }
  
  static public function existsHtmlContent($id)
  {
    $app = \Slim\Slim::getInstance();    
    $current_lang = Locale::getCurrentLang();
    
    $filename = \SiteConfig::getInstance()->get("install_path") . "/" . \SiteConfig::getInstance()->get("locale-path") . "/" . $current_lang . "/html/" . $id . ".html";
    
    return file_exists($filename);
  }
  
  static public function getHtmlContent($id)
  {
    $app = \Slim\Slim::getInstance();    
    $current_lang = Locale::getCurrentLang();
    
    $filename = \SiteConfig::getInstance()->get("install_path") . "/" . \SiteConfig::getInstance()->get("locale-path") . "/" . $current_lang . "/html/" . $id . ".html";
    
    if (file_exists($filename)) {
      return file_get_contents($filename);
    } else {
      AppLog("html-not-found", null, null, null, null, "File: $filename");
      return ""; //_("File not found:") . " <b>" . $filename . "</b>";
    }
  }

  static public function existsFileContent($id)
  {
    $app = \Slim\Slim::getInstance();    
    $current_lang = Locale::getCurrentLang();
    
    $filename = \SiteConfig::getInstance()->get("install_path") . "/" . \SiteConfig::getInstance()->get("locale-path") . "/" . $current_lang . "/files/" . $id . ".txt";
    
    return file_exists($filename);
  }  

  static public function getFileContent($id)
  {
    $app = \Slim\Slim::getInstance();    
    $current_lang = Locale::getCurrentLang();
    
    $filename = \SiteConfig::getInstance()->get("install_path") . "/" . \SiteConfig::getInstance()->get("locale-path") . "/" . $current_lang . "/files/" . $id . ".txt";
    
    if (file_exists($filename)) {
      return file_get_contents($filename);
    } else {
      AppLog("file-not-found", null, null, null, null, "File: $filename");
      return ""; //_("File not found:") . " " . $filename;
    }
  }  
    
  static public function processFile($tag, $replace_by = null)
  {
    $return_file = self::getFileContent($tag);
    if (is_array($replace_by)) {
      $return_file = str_replace(array_keys($replace_by), array_values($replace_by), $return_file);
    }

    return $return_file;
  }
  
}

