<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $color
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User|null $user
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_tag');
    }
}
