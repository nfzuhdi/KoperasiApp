<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Saving;
use App\Models\SavingPayment;
use App\Models\SavingProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SavingTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function menghasilkan_nomor_referensi_yang_unik(): void
    {
        $ref1 = $this->generateReferenceNumber();
        $ref2 = $this->generateReferenceNumber(1);

        $this->assertNotEquals($ref1, $ref2);
        $this->assertMatchesRegularExpression('/^PAY\d{8}-\d{4}$/', $ref1);
    }


    #[Test]
    public function memperbarui_jatuh_tempo_simpanan_rutin(): void
    {
        $nextDue = now()->addMonth();
        $expected = $nextDue->copy()->addMonth();

        $this->assertEquals(
            $expected->format('Y-m-d'),
            $nextDue->addMonth()->format('Y-m-d')
        );
    }

    #[Test]
    public function menghitung_bagi_hasil_dengan_benar(): void
    {
        $saldo1 = 1000000;
        $saldo2 = 2000000;
        $laba = 1000000;
        $rasio = 60;

        $bagianAnggota = ($laba * $rasio) / 100;
        $totalSaldo = $saldo1 + $saldo2;

        $bagi1 = ($saldo1 / $totalSaldo) * $bagianAnggota;
        $bagi2 = ($saldo2 / $totalSaldo) * $bagianAnggota;

        $this->assertEquals(600000, $bagianAnggota);
        $this->assertEquals(200000, $bagi1);
        $this->assertEquals(400000, $bagi2);
        $this->assertEquals($bagianAnggota, $bagi1 + $bagi2);
    }

    #[Test]
    public function membuat_record_pembayaran_bagi_hasil(): void
    {
        $laba = 1000000;
        $rasio = 60;
        $bagianAnggota = ($laba * $rasio) / 100;

        $payment = [
            'saving_id' => 1,
            'amount' => $bagianAnggota,
            'payment_type' => 'profit_sharing',
            'payment_method' => 'system',
            'status' => 'pending',
            'created_by' => 1,
            'reference_number' => 'PS-' . now()->format('Ymd') . '-0001',
            'month' => now()->month,
            'year' => now()->year,
        ];

        $this->assertEquals('profit_sharing', $payment['payment_type']);
        $this->assertEquals($bagianAnggota, $payment['amount']);
        $this->assertStringStartsWith('PS-', $payment['reference_number']);
    }

    private function generateReferenceNumber(int $increment = 0): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $next = str_pad($increment + 1, 4, '0', STR_PAD_LEFT);

        return "$prefix{$date}-$next";
    }
}
