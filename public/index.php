<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . './../');
$dotenv->load();

require __DIR__ . '/../config/database.php';

$app = AppFactory::create();

// Add error handling middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Add routes
require __DIR__ . '/../src/routes.php';

$app->run();
