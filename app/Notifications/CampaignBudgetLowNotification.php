<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignBudgetLowNotification extends Notification
{
    use Queueable;

    public function __construct(public Campaign $campaign) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $remaining = number_format($this->campaign->remainingBudgetXaf(), 0, ',', ' ').' FCFA';

        return (new MailMessage)
            ->subject(__('Alerte budget bas : :name', ['name' => $this->campaign->name]))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Votre campagne ":name" a consommé plus de 80 % de son budget.', ['name' => $this->campaign->name]))
            ->line(__('Budget restant : :remaining', ['remaining' => $remaining]))
            ->action(__('Voir mes campagnes'), route('advertiser.campaigns.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'remaining_xaf' => $this->campaign->remainingBudgetXaf(),
        ];
    }
}
