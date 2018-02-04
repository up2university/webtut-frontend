<?php

date_default_timezone_set('Europe/Lisbon');

$fs_root = "<install path>";
$full_url = "<index document url>";
  
$c = array(

  "full_url" => $full_url,
  "install_path"    => $fs_root,
  
  "default_css_url" => $full_url . "/css/embed.css",

  "email_server_host" => "localhost", // smtp mail service host
  "email_server_port" => 25, // smtp mail service port
  "email_from_address" => "", // effective outgoing mail address
  "email_from_name" => "", // name user
  "email_service_address" => "", // "public" outgoing email address
  "email_service_logo" => $fs_root . "/html/assets/ico/ms-icon-150x150.png", // file to point to logo to be included on mail message
  "email_message_top" => $fs_root . "/html/assets/imgs/top.png",
  "email_message_bottom" => $fs_root . "/html/assets/imgs/bottom.png",
  "email_message_spacer" => $fs_root . "/html/assets/imgs/spacer.png",
  "email_subject_prefix" => "WebTUT: ", // mail subject prefix
  
  "repeat-error-message" => 300, // Number of seconds before resending the send message
  
  "admin_list" => array(""), // array of admin emails

  "app_id"          => "messages",
  "app_name"        => "WebTUT",
  "app_description" => "WebTutoring App using WebRTC",
  "app_author"      => "Rui Ribeiro",
  "app_title"       => "Webtutoring App using WebRTC",
  "app_description" => "Service to support tutoring over the web using videoconference based over WebRTC",

  "db_host"         => "<db host>",
  "db_name"         => "<db name>",
  "db_username"     => "<db user name>",
  "db_password"     => "<db password>",

  "base_path"       => "/webtut",

  "defaultLocale"      => "en_GB",
  "locales"            => array(
                            array("label" => "GB", "locale" => "en_GB", "flag_alt" => "English flag", "language" => "English"), 
                            array("label" => "PT", "locale" => "pt_PT", "flag_alt" => "Portuguese flag", "language" => "Português"), 
                            array("label" => "HU", "locale" => "hu_HU", "flag_alt" => "Hugarian flag", "language" => "Magyar"), 
                            array("label" => "NO", "locale" => "nb_NO", "flag_alt" => "Norwegian flag", "language" => "Norsk"),
                            array("label" => "FR", "locale" => "fr_FR", "flag_alt" => "French flag", "language" => "Francaise"),
                            array("label" => "ES", "locale" => "es_ES", "flag_alt" => "Spanish flag", "language" => "Espagñol"),
                            array("label" => "DE", "locale" => "de_DE", "flag_alt" => "German flag", "language" => "Deutch"),
                            array("label" => "NL", "locale" => "nl_NL", "flag_alt" => "Dutch flag", "language" => "Nederlands"),
                            array("label" => "IT", "locale" => "it_IT", "flag_alt" => "Italian flag", "language" => "Italiano")
                          ),

  "locale-textdomain"  => "messages",
  "locale-path"        => "locale",
  "locale-cookie-name" => "locale",
                           
  "localeCookieName" => "locale",
         
  "ssp_base_path"       => "/usr/share/simplesamlphp/",
  "sp-default"       => "<simplesaml service provider id>",
  "sp-expected-attributes" => array("eduPersonPrincipalName"      => array("mandatory" => 1, "regex" => "^([a-zA-Z0-9\-\.\'\+]+\@[a-zA-Z0-9\-\.]+)$"), 
                                    "eduPersonScopedAffiliation"  => array("mandatory" => 1, "regex" => "(employee|staff|faculty|student)@([a-zA-Z0-9\-\.]+)$"),
                                    "mail"                        => array("mandatory" => 1, "regex" => "^([a-zA-Z0-9\-\.\'\+]+\@[a-zA-Z0-9\-\.]+)$"),
                                    "displayName"                 => array("mandatory" => 0, "regex" => "(.+)"),
    	                            "givenname"                   => array("mandatory" => 0, "regex" => "(.+)"),
  	),

  "app-administrator-list" => array("root@localhost"), // E-Mail address of application manager

  "gethostbyaddr" => true,                  // Gets the IP Reverse hostname
  
  "token_hash"    => "RANDOM STRING",       // Change it to a random string
  
  "backend_protocol"     => "https",        // Backend Server Protocol
  "backend_host"         => "webrtc-hub.fccn.pt", // Backend Server Hostname
  "backend_port"         => 8090,           // Backend Server Port
  "backend_peerjs_path"  => "/webtut",      // PeerJS absolute path
  "backend_api_path"     => "/api",         // API absolute path
  
  "stun_turn_rest_api_url" => "https://brain.lab.vvc.niif.hu/restapi/stun?", 
  "stun_turn_rest_api_key" => "XXX",  // Get one key here: https://brain.lab.vvc.niif.hu/ (eduGAIN federated service)

  // Get one key here: https://callstats.io/ 
  "callstats_app_id" => 0,
  "callstats_app_secret" => "", 
  
  "google_analytics" => "", // If Empty, disabled
);         

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
  $c["defaultLocaleLabel"] = strtoupper(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
} else {
  $c["defaultLocaleLabel"] = "EN";
}

