<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property \App\Enums\AccountType $type
 * @property string $currency_code
 * @property float|string $balance
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_archived
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency_code',
        'balance',
        'color',
        'icon',
        'is_archived',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'balance' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    protected $attributes = [
        'is_archived' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }
}
