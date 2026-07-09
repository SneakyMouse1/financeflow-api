<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property int|null $category_id
 * @property \App\Enums\TransactionType $type
 * @property float|string $amount
 * @property string $currency_code
 * @property \Carbon\Carbon $date
 * @property string|null $comment
 * @property string|null $transfer_id
 * @property int|null $related_transaction_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Transaction|null $relatedTransaction
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'type',
        'amount',
        'currency_code',
        'date',
        'comment',
        'transfer_id',
        'related_transaction_id',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'decimal:2',
        'date' => 'date',
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

    // Self-referencing relation (by default will search for transaction_id)
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'transaction_tag');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
