<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché lorsque le budget restant d'une campagne passe sous 20 %.
 */
class CampaignBudgetLow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Campaign $campaign) {}
}
