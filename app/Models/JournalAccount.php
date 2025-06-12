<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_number',
        'account_name',
        'account_type',
        'account_position',
        'is_sub_account',
        'parent_account_id',
        'opening_balance',
        'opening_balance_date',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'is_sub_account' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'balance' => 'decimal:2',
        'opening_balance_date' => 'date',
    ];

    // Relasi ke parent account
    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'parent_account_id');
    }

    // Relasi ke child accounts
    public function childAccounts(): HasMany
    {
        return $this->hasMany(JournalAccount::class, 'parent_account_id');
    }

    // Relasi ke jurnal umum
    public function jurnalUmum(): HasMany
    {
        return $this->hasMany(JurnalUmum::class, 'akun_id');
    }

    // Boot method untuk menangani event
    protected static function boot()
    {
        parent::boot();

        // Event sebelum delete
        static::deleting(function ($journalAccount) {
            // Cek apakah akun jurnal masih digunakan dalam transaksi
            if ($journalAccount->jurnalUmum()->count() > 0) {
                throw new \Exception('Akun jurnal tidak dapat dihapus karena masih digunakan dalam transaksi.');
            }

            // Cek apakah akun jurnal digunakan sebagai parent account
            if ($journalAccount->childAccounts()->count() > 0) {
                throw new \Exception('Akun jurnal tidak dapat dihapus karena masih digunakan sebagai parent account.');
            }

            // Cek apakah akun jurnal digunakan dalam produk simpanan atau pembiayaan
            // Catatan: Ini perlu disesuaikan dengan struktur database Anda
            $savingProductsCount = SavingProduct::where('journal_account_deposit_debit_id', $journalAccount->id)
                ->orWhere('journal_account_deposit_credit_id', $journalAccount->id)
                ->orWhere('journal_account_withdrawal_debit_id', $journalAccount->id)
                ->orWhere('journal_account_withdrawal_credit_id', $journalAccount->id)
                ->orWhere('journal_account_penalty_debit_id', $journalAccount->id)
                ->orWhere('journal_account_penalty_credit_id', $journalAccount->id)
                ->count();

            $loanProductsCount = LoanProduct::where('journal_account_balance_debit_id', $journalAccount->id)
                ->orWhere('journal_account_balance_credit_id', $journalAccount->id)
                ->count();

            if ($savingProductsCount > 0 || $loanProductsCount > 0) {
                throw new \Exception('Akun jurnal tidak dapat dihapus karena masih digunakan dalam konfigurasi produk.');
            }
        });
    }
}
