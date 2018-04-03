<?php
namespace Cassandra\Autoload;


require_once(__DIR__ . '/../vendor/autoload.php');

use Cassandra\Framework\AspectKernel;


$applicationAspectKernel = AspectKernel::getInstance();
$applicationAspectKernel->init(array(
        'debug' => true, // Use 'false' for production mode
        // Cache directory
        'cacheDir' => __DIR__ . '/../cache/',
        // Include paths restricts the directories where aspects should be applied, or empty for all source files
        'includePaths' => array(__DIR__ . '/../src/')
));
