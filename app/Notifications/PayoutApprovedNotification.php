<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->payout->amount_xaf, 0, ',', ' ').' FCFA';
        $mail = (new MailMessage)
            ->subject(__('Votre retrait de :amount a été validé — CM-Ads', ['amount' => $amount]))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Votre demande de retrait de :amount a été traitée et envoyée sur votre compte Mobile Money.', ['amount' => $amount]));

        if ($this->payout->gateway_reference) {
            $mail->line(__('Référence transaction : :ref', ['ref' => $this->payout->gateway_reference]));
        }

        $mail->action(__('Voir la preuve et le reçu'), route('publisher.payouts.index'))
            ->line(__('Merci d\'utiliser CM-Ads.'));

        return $mail;
    }
}
