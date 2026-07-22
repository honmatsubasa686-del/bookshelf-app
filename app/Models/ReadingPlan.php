<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'due_date',
        'status',
        'reminder_before_sent_at',
        'reminder_due_sent_at',
        'expired_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'status' => ReadingPlanStatus::class,
        'reminder_before_sent_at' => 'datetime',
        'reminder_due_sent_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
