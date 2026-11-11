<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const FULFILLMENT_STATUSES = [
        'pending' => 'Pending confirmation',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
    ];

    public const STATUSES = self::FULFILLMENT_STATUSES + [
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

    /**
     * Keep the current step available so repeated updates are harmless.
     * Later steps may be selected, but fulfillment cannot move backwards.
     *
     * @return array<string, string>
     */
    public function availableStatusOptions(): array
    {
        if ($this->archived_at !== null) {
            return [];
        }

        $currentStep = array_search($this->status, array_keys(self::FULFILLMENT_STATUSES), true);

        if ($currentStep === false) {
            return [];
        }

        return array_slice(self::FULFILLMENT_STATUSES, $currentStep, null, true);
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
