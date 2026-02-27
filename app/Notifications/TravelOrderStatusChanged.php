<?php

namespace App\Notifications;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TravelOrderStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public TravelOrder $travelOrder) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var TravelOrderStatus $status */
        $status = $this->travelOrder->status;
        /** @var Carbon $departureDate */
        $departureDate = $this->travelOrder->departure_date;
        /** @var Carbon $returnDate */
        $returnDate = $this->travelOrder->return_date;

        $statusLabel = match ($status->value) {
            'approved' => 'aprovado',
            'cancelled' => 'cancelado',
            default => $status->value,
        };
        $destination = $this->travelOrder->destination;

        return (new MailMessage)
            ->subject("Pedido de Viagem {$statusLabel}: {$destination}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Seu pedido de viagem para **{$destination}** foi **{$statusLabel}**.")
            ->line("Data de ida: {$departureDate->format('d/m/Y')}")
            ->line("Data de volta: {$returnDate->format('d/m/Y')}")
            ->line('Obrigado por utilizar nosso serviço de viagens corporativas.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        /** @var TravelOrderStatus $status */
        $status = $this->travelOrder->status;

        return [
            'travel_order_id' => $this->travelOrder->id,
            'destination' => $this->travelOrder->destination,
            'status' => $status->value,
        ];
    }
}
