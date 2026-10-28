<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty(['first_name', 'middle_name', 'last_name'])) {
                $nameParts = array_filter([
                    trim((string) $user->first_name),
                    trim((string) $user->middle_name),
                    trim((string) $user->last_name),
                ], static fn (string $part): bool => $part !== '');

                $user->name = implode(' ', $nameParts);
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_approved' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id');
    }

    public function deliveryUpdates(): HasMany
    {
        return $this->hasMany(DeliveryUpdate::class, 'rider_id');
    }
}
