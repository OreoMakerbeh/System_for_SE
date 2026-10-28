<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'pending' => 'Pending confirmation',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'user_id',
        'rider_id',
        'order_number',
        'archived_at',
        'quantity',
        'delivery_address',
        'notes',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function deliveryUpdates(): HasMany
    {
        return $this->hasMany(DeliveryUpdate::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getDisplayNumberAttribute(): int
    {
        return $this->order_number ?? $this->id;
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
