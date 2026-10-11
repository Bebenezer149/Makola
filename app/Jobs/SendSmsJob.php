<?php

namespace App\Jobs;

use App\Services\MoolreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    private string $phone;
    private string $customer_name;

    public function __construct(string $phone, string $customer_name)
    {
        $phone = trim($phone);
        $customer_name = trim($customer_name);

        if ($phone === '') {
            throw new \InvalidArgumentException('Phone number is required.');
        }

        if ($customer_name === '') {
            throw new \InvalidArgumentException('Customer name is required.');
        }

        $this->phone = $phone;
        $this->customer_name = $customer_name;
    }

    public function handle(): void
    {
        $sms = app(MoolreService::class);
        $sms->sendMessage(
            $this->phone,
            "You have a new order from {$this->customer_name} to attend to. Kindly visit your dashboard at https://blue-space-gh.vercel.app/login to review order"
        );
    }
}
