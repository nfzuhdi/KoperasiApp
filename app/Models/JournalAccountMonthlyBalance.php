<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalAccountMonthlyBalance extends Model
{
    protected $fillable = [
        'journal_account_id',
        'year',
        'month',
        'opening_balance',
        'closing_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function journalAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class);
    }
    
    // Helper method to get month name
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
    
    // Helper method to get period in format "January 2023"
    public function getPeriodAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }
}