<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Collection;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Biodata')
                        ->icon('heroicon-m-user')
                        ->schema([
                            Section::make('Identitas Utama')
                                ->schema([
                                    TextInput::make('nik')
                                        ->label('NIK')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                        ->minLength(16)
                                        ->maxLength(16)
                                        ->lazy()
                                        ->helperText('Nomor Induk Kependudukan Calon Anggota (16 digit angka)')
                                        ->rules(['regex:/^[0-9]{16}$/']),

                                    TextInput::make('npwp')
                                        ->label('NPWP')
                                        ->unique(ignoreRecord: true)
                                        ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                        ->maxLength(16)
                                        ->lazy()
                                        ->helperText('Nomor Pokok Wajib Pajak Calon Anggota'),

                                    TextInput::make('full_name')
                                        ->label('NANA LENGKAP ANGGOTA')
                                        ->required()
                                        ->helperText('Nama Lengkap sesuai KTP'),

                                    TextInput::make('nickname')
                                        ->label('NAMA PANGGILAN')
                                        ->helperText('Nama Panggilan Anggota')
                                        ->nullable(),
                                ])
                                ->columns(2),

                            Section::make('Data Kelahiran')
                                ->schema([
                                    TextInput::make('birth_place')
                                        ->label('TEMPAT LAHIR')
                                        ->required()
                                        ->helperText('Tempat Lahir Anggota sesuai KTP'),

                                    DatePicker::make('birth_date')
                                        ->label('TANGGAL LAHIR')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->helperText('Tanggal Lahir Anggota sesuai KTP')
                                        ->placeholder('DD/MM/YYYY'),

                                    Radio::make('gender')
                                        ->label('JENIS KELAMIN')
                                        ->options([
                                            'male' => 'Pria',
                                            'female' => 'Wanita',
                                        ])
                                        ->inline()
                                        ->required(),

                                    TextInput::make('religion')
                                        ->label('AGAMA')
                                        ->helperText('Agama Anggota sesuai KTP'),
                                ])
                                ->columns(2),

                            Section::make('Kontak Data')
                                ->schema([
                                    PhoneInput::make('telephone_number')
                                        ->label('NO. TELEPON')
                                        ->helperText('Nomor Telepon Anggota')
                                        ->defaultCountry('ID')
                                        ->formatOnDisplay()
                                        ->disallowDropdown()
                                        ->required(),

                                    TextInput::make('email')
                                        ->label('EMAIL')
                                        ->helperText('Email Utama Anggota')
                                        ->email(),
                                ])
                                ->columns(2),
                        ]),

                    Wizard\Step::make('Alamat')
                    ->icon('heroicon-m-map')
                        ->schema([
                            Section::make('Data Alamat')
                                ->schema([
                                    TextInput::make('RT')
                                        ->label('RT')
                                        ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                        ->maxLength(3)
                                        ->lazy()
                                        ->required()
                                        ->helperText('RT Anggota sesuai KTP'),

                                    TextInput::make('RW')
                                        ->label('RW')
                                        ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                        ->maxLength(3)
                                        ->lazy()
                                        ->required()
                                        ->helperText('RW Anggota sesuai KTP'),
                                    Select::make('province_code')
                                        ->label('PROVINSI')
                                        ->options(fn () => Province::pluck('name', 'code'))
                                        ->searchable()
                                        ->helperText('Provinsi Anggota sesuai KTP')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set) {
                                            $set('city_code', null);
                                            $set('district_code', null);
                                            $set('village_code', null);
                                        }),

                                    Select::make('city_code')
                                        ->label('KOTA/KABUPATEN')
                                        ->options(function (Forms\Get $get): Collection {
                                            return City::query()
                                                ->where('province_code', $get('province_code'))
                                                ->pluck('name', 'code');
                                        })
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->helperText('Kota/Kabupaten Anggota sesuai KTP')
                                        ->afterStateUpdated(function (Forms\Set $set) {
                                            $set('district_code', null);
                                            $set('village_code', null);
                                        }),

                                    Select::make('district_code')
                                        ->label('KECAMATAN')
                                        ->options(function (Forms\Get $get): Collection {
                                            return District::query()
                                                ->where('city_code', $get('city_code'))
                                                ->pluck('name', 'code');
                                        })
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->helperText('Kecamatan Anggota sesuai KTP')
                                        ->afterStateUpdated(fn (Forms\Set $set) => $set('village_code', null)),

                                    Select::make('village_code')
                                        ->label('KELURAHAN/DESA')
                                        ->options(function (Forms\Get $get): Collection {
                                            return Village::query()
                                                ->where('district_code', $get('district_code'))
                                                ->pluck('name', 'code');
                                        })
                                        ->searchable()
                                        ->helperText('Kelurahan/Desa Anggota sesuai KTP')
                                        ->required(),

                                    TextInput::make('postal_code')
                                        ->label('KODE POS')
                                        ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                        ->maxLength(5)
                                        ->lazy()
                                        ->helperText('Kode Pos Calon Anggota'),

                                                ])
                                                ->columns(2),
                                                
                                    Textarea::make('full_address')
                                        ->label('ALAMAT LELNGKAP')
                                        ->rows(3)
                                        ->required()
                                        ->helperText('Alamat Lengkap Anggota sesuai KTP'),
                                    
                                ]),

                    Wizard\Step::make('Pekerjaan')
                    ->icon('heroicon-m-briefcase')
                    ->schema([
                        Section::make('Data Pekerjaan')
                            ->schema([
                                Select::make('occupation')
                                    ->label('JENIS PEKERJAAN')
                                    ->options([
                                        'pns' => 'PNS',
                                        'swasta' => 'Karyawan Swasta',
                                        'wirausaha' => 'Wirausaha',
                                        'profesional' => 'Profesional',
                                        'lainnya' => 'Lainnya',
                                        'tidak_bekerja' => 'Tidak Bekerja',
                                    ])
                                    ->required()
                                    ->helperText('Jenis Pekerjaan Anggota'),

                                Textarea::make('occupation_description')
                                    ->label('DESKRIPSI PEKERJAAN')
                                    ->helperText('Deskripsi detail tentang pekerjaan Anggota')
                                    ->required(),

                                Select::make('income_source')
                                    ->label('SUMBER PENGHASILAN')
                                    ->options([
                                        'gaji' => 'Gaji',
                                        'usaha' => 'Hasil Usaha',
                                        'investasi' => 'Hasil Investasi',
                                        'orangtua' => 'Dari Orang Tua',
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->helperText('Sumber Penghasilan Utama Anggota'),

                                Select::make('income_type')
                                    ->label('KISARAN PENGHASILAN')
                                    ->options([
                                        'harian_rendah' => 'Harian: Dibawah Rp 200.000',
                                        'harian_sedang' => 'Harian: Rp 200.000 - Rp 500.000',
                                        'harian_tinggi' => 'Harian: Diatas Rp 500.000',
                                        'mingguan_rendah' => 'Mingguan: Dibawah Rp 1.000.000',
                                        'mingguan_sedang' => 'Mingguan: Rp 1.000.000 - Rp 3.000.000',
                                        'mingguan_tinggi' => 'Mingguan: Diatas Rp 3.000.000',
                                        'bulanan_rendah' => 'Bulanan: Dibawah Rp 5.000.000',
                                        'bulanan_sedang' => 'Bulanan: Rp 5.000.000 - Rp 10.000.000',
                                        'bulanan_tinggi' => 'Bulanan: Rp 10.000.000 - Rp 20.000.000',
                                        'bulanan_sangat_tinggi' => 'Bulanan: Diatas Rp 20.000.000',
                                        'tahunan_rendah' => 'Tahunan: Dibawah Rp 60.000.000',
                                        'tahunan_sedang' => 'Tahunan: Rp 60.000.000 - Rp 120.000.000',
                                        'tahunan_tinggi' => 'Tahunan: Rp 120.000.000 - Rp 240.000.000',
                                        'tahunan_sangat_tinggi' => 'Tahunan: Diatas Rp 240.000.000',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->helperText('Kisaran Penghasilan Bulanan'),
                            ])
                            ->columns(2),
                    ]),

                    Wizard\Step::make('Keluarga')
                    ->icon('heroicon-m-users')
                    ->schema([
                        Section::make('Data Pasangan')
                        ->description('Jika anggota belum menikah, abaikan bagian ini')
                            ->schema([
                                TextInput::make('spouse_nik')
                                    ->label('NIK PASANGAN')
                                    ->unique(ignoreRecord: true)
                                    ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                    ->maxLength(16)
                                    ->minLength(16)
                                    ->numeric()
                                    ->lazy()
                                    ->helperText('Nomor Induk Kependudukan Pasangan (16 digit angka)')
                                    ->rules(['regex:/^[0-9]{16}$/']),

                                TextInput::make('spouse_full_name')
                                    ->label('NAMA LENGKAP PASANGAN')
                                    ->helperText('Nama Lengkap Pasangan sesuai KTP'),

                                TextInput::make('spouse_birth_place')
                                    ->label('TEMPAT LAHIR PASANGAN')
                                    ->helperText('Tempat Lahir Pasangan sesuai KTP'),

                                DatePicker::make('spouse_birth_date')
                                    ->label('TANGGAL LAHIR PASANGAN')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Tanggal Lahir Pasangan sesuai KTP')
                                    ->placeholder('DD/MM/YYYY'),

                                Radio::make('spouse_gender')
                                    ->label('JENIS KELAMIN PASANGAN')
                                    ->options([
                                        'male' => 'Pria',
                                        'female' => 'Wanita',
                                    ])
                                    ->inline(),

                                PhoneInput::make('spouse_telephone_number')
                                    ->label('NO. TELEPON PASANGAN')
                                    ->helperText('Nomor Telepon Pasangan')
                                    ->defaultCountry('ID')
                                    // ->rule(['regex:/^08[0-9]{8,11}$/']) // hanya angka, diawali 08, panjang total 10-13 digit
                                    ->formatOnDisplay()
                                    ->disallowDropdown(),

                            ])
                            ->columns(2),

                        Section::make('Data Ahli Waris')
                            ->schema([
                                Select::make('heir_relationship')
                                    ->label('HUBUNGAN DENGAN AHLI WARIS')
                                    ->options([
                                        'suami' => 'Suami',
                                        'istri' => 'Istri',
                                        'anak' => 'Anak',
                                        'orang_tua' => 'Orang Tua',
                                        'saudara' => 'Saudara',
                                        'lainnya' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->helperText('Hubungan Anggota dengan Ahli Waris'),

                                TextInput::make('heir_nik')
                                    ->label('NIK AHLI WARIS')
                                    ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                    ->maxLength(16)
                                    ->minLength(16)
                                    ->numeric()
                                    ->lazy()
                                    ->required()
                                    ->helperText('Nomor Induk Kependudukan Ahli Waris (16 digit angka)')
                                    ->rules(['regex:/^[0-9]{16}$/']),

                                TextInput::make('heir_full_name')
                                    ->label('NAMA LENGKAP AHLI WARIS')
                                    ->required()
                                    ->helperText('Nama Lengkap Ahli Waris sesuai KTP'),

                                TextInput::make('heir_birth_place')
                                    ->label('TEMPAT LAHIR AHLI WARIS')
                                    ->required()
                                    ->helperText('Tempat Lahir Ahli Waris sesuai KTP'),

                                DatePicker::make('heir_birth_date')
                                    ->label('TANGGAL LAHIR AHLI WARIS')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->helperText('Tanggal Lahir Ahli Waris sesuai KTP')
                                    ->placeholder('DD/MM/YYYY'),

                                Radio::make('heir_gender')
                                    ->label('JENIS KELAMIN AHLI WARIS')
                                    ->options([
                                        'male' => 'Pria',
                                        'female' => 'Wanita',
                                    ])
                                    ->inline()
                                    ->required(),

                                PhoneInput::make('heir_telephone')
                                    ->label('NO. TELEPON AHLI WARIS')
                                    ->helperText('Nomor Telepon Ahli Waris')
                                    ->defaultCountry('ID')
                                    ->formatOnDisplay()
                                    ->disallowDropdown()
                                    ->required(),
                            ])
                            ->columns(2),
                    ]),

                    Wizard\Step::make('Simpanan')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            Section::make('Pilihan Rekening Simpanan')
                                ->description('Pilih produk simpanan yang ingin dibuka')
                                ->schema([
                                    Repeater::make('savings')
                                        ->label('')
                                        ->schema([
                                            Select::make('saving_product_id')
                                                ->label('Produk Simpanan')
                                                ->options(\App\Models\SavingProduct::pluck('savings_product_name', 'id'))
                                                ->required()
                                                ->searchable()
                                                ->preload(),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => 
                                            isset($state['saving_product_id']) 
                                                ? \App\Models\SavingProduct::find($state['saving_product_id'])?->savings_product_name ?? 'Simpanan Baru'
                                                : 'Produk Simpanan'
                                        )
                                        ->addActionLabel('Tambah Simpanan')
                                        ->defaultItems(0)
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->persistStepInQueryString('step') // ini supaya step tetap saat reload
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Memisahkan data simpanan dari data anggota utama
        // Ini diperlukan karena data simpanan akan disimpan di tabel terpisah
        $savingsData = $data['savings'] ?? [];
        unset($data['savings']);
        
        // Menyimpan data simpanan ke properti class untuk digunakan nanti
        // pada method afterCreate() setelah anggota berhasil dibuat
        $this->savingsData = $savingsData;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Membuat rekening simpanan untuk anggota yang baru dibuat
        // $this->record berisi instance Member yang baru saja dibuat
        $member = $this->record;
        
        // Iterasi setiap produk simpanan yang dipilih pada form
        foreach ($this->savingsData ?? [] as $savingData) {
            if (!empty($savingData['saving_product_id'])) {
                // Membuat rekening simpanan baru untuk anggota
                // dengan relasi ke produk simpanan yang dipilih
                $member->savings()->create([
                    'saving_product_id' => $savingData['saving_product_id'],
                    'balance' => $savingData['initial_deposit'] ?? 0, // Default 0 jika tidak ada setoran awal
                    'status' => 'active', // Status awal pending menunggu persetujuan
                    'created_by' => auth()->id(), // Mencatat user yang membuat rekening
                ]);
            }
        }
    }
}