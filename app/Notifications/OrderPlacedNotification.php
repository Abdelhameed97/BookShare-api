<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;
use App\Models\OrderItem;

class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Save in DB + send email
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('New Order Received')
                    ->greeting('Hello ' . $notifiable->name)
                    ->line('A new order has been placed by a client '. $this->order->client->name)
                    ->action('View Order', url('/orders/' . $this->order->id))
                    ->line('Thank you for using our platform!');
    }

    public function toDatabase($notifiable)
    {
        \Log::info('OrderPlacedNotification toDatabase executed', [
            'order_id' => $this->order->id,
            'client_id' => $this->order->client_id,
        ]);

        $bookTitles = $this->order->orderItems->pluck('book.title')->implode(', ');

        return [
            'order_id' => $this->order->id,
            'client_id' => $this->order->client_id,
            'message' => 'New order placed by client #' . $this->order->client_id . ' for books: ' . $bookTitles,
            'is_read' => false,
        ];
    }

}
