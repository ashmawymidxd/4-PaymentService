<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\PaymentController;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;


$app->group('/api/payments', function (RouteCollectorProxy $group) {
    $group->post('/charge', [PaymentController::class, 'charge']);
    $group->get('', [PaymentController::class, 'index']);
    $group->get('/{id}', [PaymentController::class, 'show']);
});