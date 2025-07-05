<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use App\Models\Book;
use App\Models\OrderItem;
use App\Models\Owner;
use App\Models\Client;

class OrderAcceptedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $client;
    public $owner;
    public $book;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->client = $order->client;
        $this->owner = $order->owner;
        $this->book = $order->order_items[0]->book ?? null; // تأكد من وجود book
    }

    public function build()
    {
        return $this->subject('Your Order Has Been Accepted')
            ->view('emails.order_accepted')
            ->with([
                'order' => $this->order,
                'client' => $this->client,
                'owner' => $this->owner,
                'book' => $this->book,
            ]);
    }
}
