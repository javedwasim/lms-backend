<?php

namespace App\Jobs;

use App\Mail\EnrolledMail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\WebhookClient\Models\WebhookCall;

class HandlePaymentIntentSucceeded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $webhookCall;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->webhookCall->payload['data']['object'];

        $product = $order['metadata']['Product Name'] ?? null;
        $customerEmail = $order['receipt_email'];

        $randomPassword = $this->generateRandomString(8);

        if (
            $product == 'UCAT Question Bank & Video Course' ||
            $product == 'UCAT Live Course (Video Course, 4000 Qs, Live UCAT Day)' ||
            $product == 'UCAT Live Course (Video Course, 8000 Qs, 2 Live UCAT Days)' ||
            $product == 'UCAT Live Course (Video Course, 8000 Qs, 2 Live UCAT Days, Interview Course + 8h 1-1 Tutor)'
        ) {
            // get the user with email
            $user = User::where('email', $customerEmail)->first();

            if ($user == null) {
                // create user with email and a random password
                $user = User::create([
                    'name' => explode('@', $customerEmail)[0], // get name from the email - remove @ and everything after
                    'email' => $customerEmail,
                    'password' => Hash::make($randomPassword),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                ]);


                // email the credentials to the user
                $userData = (object) [
                    'subject' => 'Your UCAT account has been created',
                    'password' => $randomPassword,

                ];

                Mail::to($customerEmail)->send(new EnrolledMail($userData));
            }

            // 1. add new order in order_tbl
            $order = Order::create([
                'user_id' => $user->id,
                'package_for' => 1, // use 1 for now
                'total_amount' =>  $order['amount'] / 100, // object.amount
                'payment_status' => 1,
            ]);

            // 2. add new row in order_detail table with package/course id as particular_record_id
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'package_for' => $order->package_for,
                'particular_record_id' => 29, // 29 for UCAT
                'package_id' => 49, // 49 for 'UCAT Course' package
                'price' => $order->total_amount,
                'expiry_date' => date('Y-m-d H:i:s', strtotime('+1 year')), // set an expiry of 1 year
            ]);
        }
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}
