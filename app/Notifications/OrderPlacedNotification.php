<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        // نحمل البيانات المطلوبة من البداية لتفادي مشاكل لاحقاً
        $this->order = $order->load('orderItems.book', 'client', 'owner');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        \Log::info('Sending email to: ' . $notifiable->email);

        $client = $this->order->client;

        return (new MailMessage)
            ->subject('📦 New Order Request from ' . $client->name)
            ->view('emails.order_request', [
                'order'  => $this->order,
                'books'  => $this->order->orderItems->pluck('book')->filter(),
                'client' => $client,
                'owner'  => $this->order->owner,
            ]);
    }

    /**
     * Store notification in database.
     */
    public function toDatabase($notifiable)
    {
        $client = $this->order->client;
        $bookTitles = $this->order->orderItems->pluck('book.title')->implode(', ');

        return [
            'order_id'    => $this->order->id,
            'client_id'   => $client->id,
            'client_name' => $client->name,
            'message'     => "📚 New order from {$client->name} for: {$bookTitles}",
            'total'       => $this->order->total_price,
        ];
    }
}
