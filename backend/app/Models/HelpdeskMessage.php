<?php

namespace App\Models;

use App\Traits\EncryptsFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskMessage extends Model
{
    use HasFactory, EncryptsFields;

    protected $fillable = [
        'helpdesk_chat_id',
        'sender_type', // user, agent, bot
        'sender_id',   // nullable for bot
        'content',
    ];

    /**
     * Fields that should be encrypted
     *
     * @var array
     */
    protected $encrypted = [
        'content',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(HelpdeskChat::class, 'helpdesk_chat_id');
    }
}


