<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property \App\Enums\BudgetPeriod $period
 * @property float|string $amount
 * @property string $currency_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Category|null $category
 */
class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'period',
        'amount',
        'currency_code',
    ];

    protected $casts = [
        'period' => BudgetPeriod::class,
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
