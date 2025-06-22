<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\Owner;
use App\Models\Client;
use App\Models\Book;
use App\Models\User;

class OrderCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'New Order Notification',
    //     );
    // }

    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'emails.order_created',
    //     );
    // }

    // public function attachments(): array
    // {
    //     return [];
    // }

   public function build()
    {
        return $this->subject('New Order Request on BookShare')
            ->view('emails.order-request')
            ->with([
                'order' => $this->order,
                'client' => $this->order->client,
                'owner' => $this->order->owner,
                'book' => $this->order->book, // تأكد إنها موجودة ومحملة مسبقاً
            ]);
    }
} 