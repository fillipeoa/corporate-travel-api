<?php

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrder extends Model
{
    /** @use HasFactory<\Database\Factories\TravelOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TravelOrderStatus::class,
            'departure_date' => 'date',
            'return_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the travel order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
