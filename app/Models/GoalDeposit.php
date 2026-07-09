<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $goal_id
 * @property int|null $transaction_id
 * @property float|string $amount
 * @property string|null $comment
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Goal|null $goal
 * @property-read \App\Models\Transaction|null $transaction
 */
class GoalDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'transaction_id',
        'amount',
        'comment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
