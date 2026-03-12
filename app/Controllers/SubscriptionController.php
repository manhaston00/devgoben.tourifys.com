<?php

namespace App\Controllers;

class SubscriptionController extends BaseController
{
    public function expired()
    {
        return view('subscription/expired', [
            'title' => 'Subscription Expired',
        ]);
    }
}