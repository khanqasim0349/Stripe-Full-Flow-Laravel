<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        return view('product.index', compact('products'));
    }

    public function checkout(Request $request)
    {
        $key = "sk_test_51QZQqFEEHev96Z1rIroPnca2gxI4InXSamYdY5DbGmekN2lT5laR5CmMxJALyCNuwTNY2rlJRUZDtyfXJsawknwj00sj7Cxsw5";
        \Stripe\Stripe::setApiKey($key);

        // $email = filter_var($request->input('email'), FILTER_SANITIZE_EMAIL);

        // if (!$email) {
        //     return redirect()->back()->withErrors(['email' => 'Invalid email address provided.']);
        // }
        // dd('qasim');
        $lineItems = [];
        $products = Product::all();
        $totalPrice = 0;

        foreach ($products as $product) {
            $totalPrice += $product->price;
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                        'images' => [$product->image],
                    ],
                    'unit_amount' => $product->price * 100,
                ],
                'quantity' => 2,
            ];
        }

        try {
            $session = \Stripe\Checkout\Session::create([
                'line_items' => $lineItems,
                'mode' => 'payment',
                // 'customer_email' => $email,
                'success_url' => route('checkout.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', [], true),
            ]);

            $order = new Order();
            $order->status = 'unpaid';
            $order->total_price = $totalPrice;
            $order->session_id = $session->id;
            $order->save();

            return redirect($session->url);
        } catch (Exception $e) {
            dd($e);
            Log::error('Error creating Stripe session: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to create payment session. Please try again.']);
        }
    }

    public function success(Request $request)
    {
        $key = "sk_test_51QZQqFEEHev96Z1rIroPnca2gxI4InXSamYdY5DbGmekN2lT5laR5CmMxJALyCNuwTNY2rlJRUZDtyfXJsawknwj00sj7Cxsw5";
        \Stripe\Stripe::setApiKey($key);

        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                throw new NotFoundHttpException('Session ID not provided.');
            }

            try {
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                if (!$session) {
                    throw new NotFoundHttpException('Session not found.');
                }

                $customerDetails = $session->customer_details;

                $customer = [
                    'name' =>  $customerDetails->name  ?? 'Unknown',
                    'email' => $customerDetails->email ?? 'Unknown',
                    'phone' => $customerDetails->phone ?? 'Unknown',
                ];
                $order = Order::where('session_id', $session->id)->first();

                if(!$order){
                    return response()->json(['Error','order not found'],404);
                }
                if ($order->status == 'unpaid') {
                    $order->status = 'paid';
                    $order->save();
                    return response()->json(['Success', "order is paid"], 200);
                    //send email to customer that say thanks your order is paid
                 }
            } catch (Exception $e) {
                throw new NotFoundHttpException();
            }

            return view('product.checkout-success', compact('customer'));
        } catch (Exception $e) {
            Log::error('Error retrieving Stripe session: ' . $e->getMessage());
            // return redirect()->route('product.index')->withErrors(['error' => 'Payment success verification failed.']);
        }
    }

    public function cancel()
    {
        return view('product.checkout-cancel');
    }
    public function webhook()
    {
        $stripeSecretKey = "sk_test_51QZQqFEEHev96Z1rIroPnca2gxI4InXSamYdY5DbGmekN2lT5laR5CmMxJALyCNuwTNY2rlJRUZDtyfXJsawknwj00sj7Cxsw5";
        \Stripe\Stripe::setApiKey($stripeSecretKey);
        $endpoint_secret = 'whsec_0243144c6ae78dbf00c19450ede1a2f66a3ce133cd3d56c0a3675075f5e0ce75';

        $payload = @file_get_contents('php://input');
        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            // echo '⚠️  Webhook error while parsing basic request.';
            // http_response_code(400);
            return response('', 400);
            // exit();
        }
        if ($endpoint_secret) {
            // Only verify the event if there is an endpoint secret defined
            // Otherwise use the basic decoded event
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $endpoint_secret
                );
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                return response('', 400);
            }
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; 
                $sessionId = $session->id;

                $order = Order::where('session_id', $session->id)->first();
                if ($order && $order->status == 'unpaid') {
                    $order->status = 'paid';
                    $order->save();
                    return response()->json(['Success', "Order is paid"], 200);
                    //send email to customer that say thanks your order is paid
                 }
                // $order->status = 'paid';
                // $order->save();
                break;
            case 'payment_method.attached':
                $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
                // Then define and call a method to handle the successful attachment of a PaymentMethod.
                // handlePaymentMethodAttached($paymentMethod);
                break;
            default:
                // Unexpected event type
                error_log('Received unknown event type');
        }

        return response(' ');
    }
}
