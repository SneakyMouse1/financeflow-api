<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transaction_id
 * @property string $file_name
 * @property string $mime
 * @property int $size
 * @property string $path
 * @property \App\Models\Transaction|null $transaction
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'file_name',
        'mime',
        'size',
        'path',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
