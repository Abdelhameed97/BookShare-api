<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;
use App\Models\OrderItem;
// App\Notifications\OrderStatusUpdatedNotification.php



class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public $order;
    public $status;

    public function __construct($order, $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Save in DB + send email
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Order Has Been ' . ucfirst($this->status))
            ->view("emails.order_{$this->status}", [
                'client' => $notifiable,
                'order' => $this->order,
                'books' => $this->order->orderItems->pluck('book')->filter(), // ✅ كل الكتب هتظهر هنا
                'owner' => $this->order->owner,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->status,
            'message' => "Your order #{$this->order->id} has been {$this->status}.",
        ];
    }
}
