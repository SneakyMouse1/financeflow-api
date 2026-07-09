<?php

namespace App\Models;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $category_id
 * @property string $name
 * @property \App\Enums\TransactionType $type
 * @property float|string $amount
 * @property string $currency_code
 * @property \App\Enums\RecurringFrequency $frequency
 * @property \Carbon\Carbon $next_run_at
 * @property string|null $comment
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Category|null $category
 */
class RecurringTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'name',
        'type',
        'amount',
        'currency_code',
        'frequency',
        'next_run_at',
        'comment',
        'is_active',
    ];

    protected $casts = [
        'type'        => TransactionType::class,
        'frequency'   => RecurringFrequency::class,
        'amount'      => 'decimal:2',
        'next_run_at' => 'date',
        'is_active'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
