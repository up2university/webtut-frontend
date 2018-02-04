<?php

// More information on how to configure IDIORM
// http://idiorm.readthedocs.org/en/latest/configuration.html

// Make a new connection
ORM::configure('mysql:host=' . \SiteConfig::getInstance()->get("db_host") . ';dbname=' . \SiteConfig::getInstance()->get("db_name") );
ORM::configure('username', \SiteConfig::getInstance()->get("db_username"));
ORM::configure('password', \SiteConfig::getInstance()->get("db_password"));

require_once '../models/Session.php';
require_once '../models/Meeting.php';
require_once '../models/Participation.php';
require_once '../models/Support.php';