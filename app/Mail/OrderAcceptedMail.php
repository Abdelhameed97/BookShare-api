<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class OrderAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order, $book, $owner, $client;

    /**
     * Create a new message instance.
     */

    public function __construct($order)
    {
        $this->order = $order;
        $this->book = $order->orderItems->first()->book;
        $this->owner = $order->owner;
        $this->client = $order->client;
    }

    public function build()
    {
        return $this->subject('Your Order Has Been Accepted')
                    ->view('emails.order_accepted');
    }
}
