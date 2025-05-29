<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class Member extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'member_id',
        'nik',
        'npwp',
        'full_name',
        'nickname',
        'mother_name',
        'father_name',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'member_photo',

        // Contact data
        'telephone_number',
        'email',

        // Address data
        'RT',
        'RW',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'full_address',
        'postal_code',

        // Job data
        'occupation',
        'occupation_description',
        'income_source',
        'income_type',

        // Spouse data
        'spouse_nik',
        'spouse_full_name',
        'spouse_birth_place',
        'spouse_birth_date',
        'spouse_gender',
        'spouse_telephone_number',
        'spouse_email',

        // Beneficiary data
        'heir_relationship',
        'heir_nik',
        'heir_full_name',
        'heir_birth_place',
        'heir_birth_date',
        'heir_gender',
        'heir_telephone',
        
        // Status
        'member_status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'spouse_birth_date' => 'date',
        'heir_birth_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            // Auto-generate member ID with MMR prefix if not provided
            if (empty($member->member_id)) {
                $member->member_id = self::generateMemberID();
            }
        });
    }

    public static function generateMemberID()
    {
        $prefix = 'MMR';

        // Get the latest member number
        $latestMember = self::orderBy('id', 'desc')->first();

        if (!$latestMember) {
            // If no members yet, start with MMR00001
            $nextNumber = 1;
        } else {
            // Extract the number from the latest member ID
            $lastID = $latestMember->member_id;
            if (preg_match('/MMR(\d+)/', $lastID, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                // Fallback if pattern doesn't match
                $nextNumber = 1;
            }
        }

        // Format with leading zeros (5 digits)
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the province associated with the member.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    /**
     * Get the city associated with the member.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    /**
     * Get the district associated with the member.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    /**
     * Get the village associated with the member.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    /**
     * Get the savings accounts associated with the member.
     */
    public function savings()
    {
        return $this->hasMany(Saving::class);
    }
}







