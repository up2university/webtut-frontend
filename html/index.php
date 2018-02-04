<?php

require '../app/libs/SiteConfig.php';

require '../vendor/autoload.php';

// Database connection
require '../app/database.php';

// Start init files
require '../app/bootstrap.php';

// Route
require '../app/routes.php';

$app->run();


