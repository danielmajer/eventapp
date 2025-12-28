<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\EncryptsFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory, EncryptsFields, Auditable;

    protected $fillable = [
        'title',
        'occurs_at',
        'description',
        'user_id',
    ];

    protected $casts = [
        'occurs_at' => 'datetime',
    ];

    /**
     * Fields that should be encrypted
     *
     * @var array
     */
    protected $encrypted = [
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


