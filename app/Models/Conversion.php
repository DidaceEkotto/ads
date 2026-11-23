<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['campaign_id', 'click_id', 'visitor_hash', 'event_name', 'value_xaf', 'created_at'])]
class Conversion extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_xaf' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
