<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class OrderRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

     public $order, $books, $owner, $client;

    /**
     * Create a new message instance.
     */


    public function __construct($order)
    {
        $this->order = $order;
        $this->books = $order->orderItems->pluck('book');
        $this->owner = $order->owner;
        $this->client = $order->client;
    }


    public function build()
    {
        return $this->subject('Your Order Has Been Rejected')
                    ->view('emails.order_rejected')
                    ->with([
                        'order' => $this->order,
                        'books' => $this->books,
                        'owner' => $this->owner,
                        'client' => $this->client,
                    ]);
    }
}
