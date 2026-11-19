<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Si Apache llegó aquí desde el .htaccess de la carpeta raíz (proyecto dentro de htdocs),
// la URL pública no lleva "/public": se ajusta la ruta base para que los enlaces queden bien.
if (isset($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI'])
    && str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php')
    && !str_contains(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/public/')) {
    $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/public/index.php')).'/index.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
