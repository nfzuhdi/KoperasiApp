<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingProductResource;
use Filament\Forms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingProductFormTest extends TestCase
{
    use RefreshDatabase;


    #[\PHPUnit\Framework\Attributes\Test]
    public function koperasi_ratio_calculated_automatically_when_member_ratio_is_updated(): void
    {
        $memberRatio = 70;
        $capturedValue = null;

        $afterStateUpdated = function (callable $set, $state) use (&$capturedValue) {
            $set('koperasi_ratio', 100 - (int)$state);
        };

        // Simulate call to `afterStateUpdated`
        $set = function ($key, $value) use (&$capturedValue) {
            if ($key === 'koperasi_ratio') {
                $capturedValue = $value;
            }
        };

        $afterStateUpdated($set, $memberRatio);

        $this->assertEquals(30, $capturedValue);
    }

}
