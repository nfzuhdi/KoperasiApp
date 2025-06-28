<?php

namespace Tests\Unit;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_member_id_automatically_when_creating_new_member()
    {
        $member = Member::create([
            'nik' => '1234567890123456',
            'full_name' => 'John Doe',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'telephone_number' => '+6281234567890',
            'RT' => '001',
            'RW' => '002',
            'province_code' => '31',
            'city_code' => '3171',
            'district_code' => '317101',
            'village_code' => '3171011001',
            'full_address' => 'Jl. Test No. 123',
            'occupation' => 'swasta',
            'occupation_description' => 'Software Developer',
            'income_source' => 'gaji',
            'income_type' => 'bulanan_sedang',
            'heir_relationship' => 'istri',
            'heir_nik' => '1234567890123457',
            'heir_full_name' => 'Jane Doe',
            'heir_birth_place' => 'Jakarta',
            'heir_birth_date' => '1992-01-01',
            'heir_gender' => 'female',
            'heir_telephone' => '+6281234567891',
        ]);

        $this->assertStringStartsWith('MMR', $member->member_id);
        $this->assertEquals('MMR00001', $member->member_id);
    }


    #[Test]
    public function it_does_not_override_manually_set_member_id()
    {
        $customMemberID = 'MMR99999';
        $member = Member::create([
            'member_id' => $customMemberID,
            'nik' => '1234567890123456',
            'full_name' => 'John Doe',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'telephone_number' => '+6281234567890',
            'RT' => '001',
            'RW' => '002',
            'province_code' => '31',
            'city_code' => '3171',
            'district_code' => '317101',
            'village_code' => '3171011001',
            'full_address' => 'Jl. Test No. 123',
            'occupation' => 'swasta',
            'occupation_description' => 'Software Developer',
            'income_source' => 'gaji',
            'income_type' => 'bulanan_sedang',
            'heir_relationship' => 'istri',
            'heir_nik' => '1234567890123457',
            'heir_full_name' => 'Jane Doe',
            'heir_birth_place' => 'Jakarta',
            'heir_birth_date' => '1992-01-01',
            'heir_gender' => 'female',
            'heir_telephone' => '+6281234567891',
        ]);

        $this->assertEquals($customMemberID, $member->member_id);
    }


    #[Test]
    public function it_has_savings_relationship()
    {
        $member = Member::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $member->savings());
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Member::create(['full_name' => 'No NIK']);
    }

    // #[Test]
    // // public function it_validates_nik_max_length()
    // // {
    // //     $this->expectException(\Illuminate\Database\QueryException::class);
    // //     Member::create([
    // //         'nik' => '1234567890123456111', // 17 chars
    // //         'full_name' => 'Long NIK'
    // //     ]);
    // // }

    #[Test]
    public function it_validates_nik_uniqueness()
    {
        Member::factory()->create(['nik' => '1234567890123456']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Member::create([
            'nik' => '1234567890123456',
            'full_name' => 'Duplicate NIK'
        ]);
    }

    #[Test]
    public function it_accepts_valid_email_format()
    {
        $member = Member::factory()->create(['email' => 'john.doe@example.com']);
        $this->assertEquals('john.doe@example.com', $member->email);
    }
}
