<?php

namespace App\Enums;

/**
 * Types d'écritures dans le grand livre financier (ledger).
 */
enum TransactionType: string
{
    case Deposit = 'deposit';
    case AdSpend = 'ad_spend';
    case PublisherEarning = 'publisher_earning';
    case Payout = 'payout';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => __('Recharge de solde'),
            self::AdSpend => __('Dépense publicitaire'),
            self::PublisherEarning => __('Gain éditeur'),
            self::Payout => __('Retrait'),
            self::Refund => __('Remboursement'),
            self::Adjustment => __('Ajustement manuel'),
        };
    }
}
