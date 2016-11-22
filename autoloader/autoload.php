<?php
namespace Cassandra\Autoload;

require_once('Psr4AutoloaderClass.php');
require_once(__DIR__ . '/../vendor/autoload.php');

// register the autoloader
$loader = new Psr4AutoloaderClass();
$loader->register();

//$source_path = __DIR__ . '/../src';
$vendor_name = 'Cassandra';

$loader->addNamespace($vendor_name . '\\Framework', __DIR__ . '/../src/framework');
$loader->addNamespace($vendor_name . '\\Test', __DIR__ . '/../tests');
//$loader->addNamespace($vendor_name . '\\Framework\AOP', __DIR__ . '/../src/framework/aop');
