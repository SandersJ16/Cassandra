<?php
namespace Cassandra\Autoload;


require_once(__DIR__ . '/../vendor/autoload.php');
require_once('Psr4AutoloaderClass.php');


use Go\Core\AspectKernel;
use Go\Core\AspectContainer;

$applicationAspectKernel = ApplicationAspectKernel::getInstance();
$applicationAspectKernel->init(array(
        'debug' => true, // Use 'false' for production mode
        // Cache directory
        'cacheDir' => __DIR__ . '/../cache/',
        // Include paths restricts the directories where aspects should be applied, or empty for all source files
        'includePaths' => array(
            //__DIR__ . '/../src/'
        )
));






// register the autoloader
$loader = new Psr4AutoloaderClass();
$loader->register();

//$source_path = __DIR__ . '/../src';
$vendor_name = 'Cassandra';

$loader->addNamespace($vendor_name . '\\Framework', __DIR__ . '/../src/framework');
$loader->addNamespace($vendor_name . '\\Test', __DIR__ . '/../tests');
//$loader->addNamespace($vendor_name . '\\Framework\AOP', __DIR__ . '/../src/framework/aop');



