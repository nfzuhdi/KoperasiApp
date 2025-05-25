<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Collection;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Using Grid instead of Split for better responsive layout
                \Filament\Forms\Components\Grid::make(3)
                    ->schema([
                        Section::make('Gambar Anggota')
                            ->schema([
                                FileUpload::make('member_photo')
                                    ->image()
                                    ->imageEditor()
                                    ->avatar()
                                    ->label('')
                                    ->circleCropper()
                                    ->directory('member-photos')
                                    ->helperText('Upload foto anggota (format: jpg, png, jpeg, max: 2MB)')
                                    ->maxSize(2048) // 2MB

                            ])
                            ->columnSpan(1),
                                
                        Tabs::make('Member')
                            ->tabs([
                                        Tabs\Tab::make('Biodata')
                                            ->icon('heroicon-m-user')
                                            ->schema([
                                                Section::make('Identitas Utama')
                                                    ->schema([
                                                        TextInput::make('member_id')
                                                            ->label('ID ANGGOTA')
                                                            ->required()
                                                            ->disabledOn('edit')
                                                            ->unique(ignoreRecord: true)
                                                            ->helperText('ID Anggota (Auto Generated)'),
                                                        TextInput::make('nik')
                                                            ->label('NIK')
                                                            ->required()
                                                            ->unique(ignoreRecord: true)
                                                            ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                                            ->maxLength(16)
                                                            ->lazy()
                                                            ->helperText('Nomor Induk Kependudukan Anggota'),

                                                        TextInput::make('npwp')
                                                            ->label('NPWP')
                                                            ->unique(ignoreRecord: true)
                                                            ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                                            ->maxLength(16)
                                                            ->lazy()
                                                            ->helperText('Nomor Pokok Wajib Pajak Anggota'),

                                                        TextInput::make('full_name')
                                                            ->label('NAMA LENGKAP')
                                                            ->required()
                                                            ->helperText('Nama Lengkap Anggota sesuai KTP'),

                                                        TextInput::make('birth_place')
                                                            ->label('TEMPAT LAHIR')
                                                            ->required()
                                                            ->helperText('Tempat Lahir Anggota sesuai KTP'),

                                                        DatePicker::make('birth_date')
                                                            ->label('TANGGAL LAHIR')
                                                            ->native(false)
                                                            ->displayFormat('d/m/Y')
                                                            ->required()
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
                                                    ])
                                                    ->columns(2),
                                            ]),
                                            
                                        Tabs\Tab::make('Kontak')
                                            ->icon('heroicon-m-phone')
                                            ->schema([
                                                Section::make('Data Kontak')
                                                    ->schema([
                                                        TextInput::make('telephone_number')
                                                            ->label('NO. TELEPON')
                                                            ->helperText('Nomor Telepon Anggota')
                                                            ->tel(),

                                                        TextInput::make('email')
                                                            ->label('EMAIL')
                                                            ->helperText('Email Utama Anggota')
                                                            ->email(),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Tabs\Tab::make('Alamat')
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
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(fn (callable $set) => $set('city_code', null))
                                                            ->helperText('Provinsi Anggota sesuai KTP'),

                                                        Select::make('city_code')
                                                            ->label('KOTA/KABUPATEN')
                                                            ->options(fn (callable $get) => 
                                                                City::where('province_code', $get('province_code'))
                                                                    ->pluck('name', 'code')
                                                            )
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(fn (callable $set) => $set('district_code', null))
                                                            ->helperText('Kota/Kabupaten Anggota sesuai KTP'),

                                                        Select::make('district_code')
                                                            ->label('KECAMATAN')
                                                            ->options(fn (callable $get) => 
                                                                District::where('city_code', $get('city_code'))
                                                                    ->pluck('name', 'code')
                                                            )
                                                            ->searchable()
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(fn (callable $set) => $set('village_code', null))
                                                            ->helperText('Kecamatan Anggota sesuai KTP'),

                                                        Select::make('village_code')
                                                            ->label('KELURAHAN/DESA')
                                                            ->options(fn (callable $get) => 
                                                                Village::where('district_code', $get('district_code'))
                                                                    ->pluck('name', 'code')
                                                            )
                                                            ->searchable()
                                                            ->required()
                                                            ->helperText('Kelurahan/Desa Anggota sesuai KTP'),

                                                        Textarea::make('full_address')
                                                            ->label('ALAMAT LENGKAP')
                                                            ->required()
                                                            ->helperText('Alamat Lengkap Anggota sesuai KTP'),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Tabs\Tab::make('Pekerjaan')
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
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->helperText('Jenis Pekerjaan Anggota'),

                                                        Textarea::make('occupation_description')
                                                            ->label('DESKRIPSI PEKERJAAN')
                                                            ->rows(3)
                                                            ->helperText('Deskripsi detail tentang pekerjaan Anggota')
                                                            ->required(),

                                                        Select::make('income_source')
                                                            ->label('SUMBER PENGHASILAN')
                                                            ->options([
                                                                'gaji' => 'Gaji',
                                                                'usaha' => 'Hasil Usaha',
                                                                'investasi' => 'Hasil Investasi',
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
                                                            ->helperText('Kisaran Penghasilan Bulanan'),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Tabs\Tab::make('Data Pasangan')
                                            ->icon('heroicon-m-heart')
                                            ->schema([
                                                Section::make('Data Pasangan')
                                                    ->schema([
                                                        TextInput::make('spouse_nik')
                                                            ->label('NIK PASANGAN')
                                                            ->unique(ignoreRecord: true)
                                                            ->hint(fn ($state, $component) => strlen($state) . '/' . $component->getMaxLength())
                                                            ->maxLength(16)
                                                            ->lazy()
                                                            ->helperText('Nomor Induk Kependudukan Pasangan'),

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
                                                            ->label('JENIS KELAMIN')
                                                            ->options([
                                                                'male' => 'Pria',
                                                                'female' => 'Wanita',
                                                            ])
                                                            ->inline(),

                                                        TextInput::make('spouse_telephone_number')
                                                            ->label('NO. TELEPON PASANGAN')
                                                            ->helperText('Nomor Telepon Pasangan')
                                                            ->tel(),

                                                        TextInput::make('spouse_email')
                                                            ->label('EMAIL PASANGAN')
                                                            ->helperText('Email Pasangan')
                                                            ->email(),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Tabs\Tab::make('Ahli Waris')
                                            ->icon('heroicon-m-user-group')
                                            ->schema([
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
                                                            ->lazy()
                                                            ->helperText('Nomor Induk Kependudukan Ahli Waris'),

                                                        TextInput::make('heir_full_name')
                                                            ->label('NAMA LENGKAP AHLI WARIS')
                                                            ->required()
                                                            ->helperText('Nama Lengkap Ahli Waris sesuai KTP'),

                                                        TextInput::make('heir_birth_place')
                                                            ->label('TEMPAT LAHIR AHLI WARIS')
                                                            ->helperText('Tempat Lahir Ahli Waris sesuai KTP'),

                                                        DatePicker::make('heir_birth_date')
                                                            ->label('TANGGAL LAHIR AHLI WARIS')
                                                            ->native(false)
                                                            ->displayFormat('d/m/Y')
                                                            ->helperText('Tanggal Lahir Ahli Waris sesuai KTP')
                                                            ->placeholder('DD/MM/YYYY'),

                                                        Radio::make('heir_gender')
                                                            ->label('JENIS KELAMIN')
                                                            ->options([
                                                                'male' => 'Pria',
                                                                'female' => 'Wanita',
                                                            ])
                                                            ->inline(),

                                                        TextInput::make('heir_telephone')
                                                            ->label('NO. TELEPON AHLI WARIS')
                                                            ->helperText('Nomor Telepon Ahli Waris')
                                                            ->tel(),
                                                    ])
                                                    ->columns(2),
                                            ]),
                                    ])
                            ->columnSpan(2)
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}