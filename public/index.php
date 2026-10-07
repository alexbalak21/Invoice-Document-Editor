<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Router;

$router = new Router();
require ROOT . '/routes/web.php';
$router->dispatch();
