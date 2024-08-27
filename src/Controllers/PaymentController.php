<?php

namespace App\Controllers;

use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Payment;
// validation
use Respect\Validation\Validator as v;

class PaymentController
{
    protected $httpClient;

    public function __construct()
    {
        // Stripe::setApiKey('pk_test_51PeMiGEqDzLeCctweTr2x590IIJcNVNZHmnwMoVjL6x4QwR5pBsjjawyEg1lvHBVNLdOw7AqrJyfdvuUInEnuq0f00Y94sFg6n');
        Stripe::setApiKey('sk_test_51PeMiGEqDzLeCctwO5TiF9iRJaukZ83h4VzoaUDBCHohvfjCpUw9EoDi3BvH6V3R4wedsAFGclvXHj13RM5BJR1F00NBir92pq');
        $this->httpClient = new HttpClient();
    }

    public function charge(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        $token = $data['token'];
        $amount = $data['amount'];
        $currency = $data['currency'] ?? 'usd';
        $orderId = $data['order_id'];
        $userId = $data['user_id'];

        // Verify order with the order-service
        $order = $this->verifyOrder($orderId, $userId);
        if (!$order) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Invalid order or user']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Create Stripe charge
            $charge = Charge::create([
                'amount' => $amount * 100, // amount in cents
                'currency' => $currency,
                'source' => $token,
                'description' => 'Payment for Order ID: ' . $orderId,
            ]);

            // Record the payment
            $payment = new Payment([
                'order_id' => $orderId,
                'stripe_charge_id' => $charge->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);
            $payment->save();

            // Update order status (optional, if the order-service has an endpoint for this)
            $this->updateOrderStatus($orderId, 'paid');

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'charge' => $charge,
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (ApiErrorException $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    protected function verifyOrder($orderId, $userId)
    {
        try {
            $response = $this->httpClient->get("http://127.0.0.1:30000/api/orders/{$orderId}");
            // validate user_id here
            $user_id = json_decode($response->getBody(), true)['order']['user_id'];
            if ($userId != $user_id) {
                return null;
            }else{
                return json_decode($response->getBody(), true)['order'];
            }

            // print_r($response->getBody()->getContents());

            // $orderData = json_decode($response->getBody(), true);
            // return $orderData['status'] === 'success' ? $orderData['order'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Optional method to update order status
    protected function updateOrderStatus($orderId, $status)
    {
        try {
            $this->httpClient->put("http://127.0.0.1:30000/api/orders/status/{$orderId}", [
                'json' => ['status' => $status],
            ]);
        } catch (\Exception $e) {
            // Handle the exception
        }
    }

    // get all payments
    public function index(Request $request, Response $response, $args)
    {
        $payments = Payment::all();
        $response->getBody()->write(json_encode($payments));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // get payment by id
    public function show(Request $request, Response $response, $args)
    {
        $payment = Payment::find($args['id']);
        $response->getBody()->write(json_encode($payment));
        return $response->withHeader('Content-Type', 'application/json');
    }

}
