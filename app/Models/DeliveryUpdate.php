<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryUpdate extends Model
{
    public const MILESTONES = [
        'picked_up' => 'Picked up by rider',
        'in_transit' => 'On the way',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
    ];

    protected $fillable = [
        'order_id',
        'rider_id',
        'status',
        'message',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}