<?php

namespace App\Models;

use App\Enums\GoalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property float|string $target_amount
 * @property float|string $current_amount
 * @property string $currency_code
 * @property \App\Enums\GoalStatus $status
 * @property \Carbon\Carbon|null $deadline
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 */
class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'currency_code',
        'status',
        'deadline',
    ];

    protected $casts = [
        'status' => GoalStatus::class,
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(GoalDeposit::class);
    }
}
