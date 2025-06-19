<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Form;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make(3)
                    ->schema([
                        \Filament\Forms\Components\Grid::make(1)
                            ->schema([
                                Section::make('Gambar Anggota')
                                    ->schema([
                                        FileUpload::make('member_photo')
                                            ->image()
                                            ->disabled()
                                            ->avatar()
                                            ->label('')
                                            ->directory('member-photos')
                                            ->helperText('Foto anggota')
                                    ]),
                                    
                                Section::make('Status Anggota')
                                    ->schema([
                                        Placeholder::make('member_status')
                                            ->label('Status Keanggotaan')
                                            ->content(fn ($record) => match ($record->member_status) {
                                                'pending' => 'Dalam Proses',
                                                'active' => 'Aktif',
                                                'delinquent' => 'Bermasalah',
                                                'terminated' => 'Keluar',
                                                default => ucfirst($record->member_status ?? '-'),
                                            }),
                                        Placeholder::make('Created at')
                                            ->label('Dibuat Pada')
                                            ->content(fn ($record) => $record->created_at->format('d/m/Y H:i:s')),
                                        Placeholder::make('Updated at')
                                            ->label('Terakhir Edit')
                                            ->content(fn ($record) => $record->updated_at->format('d/m/Y H:i:s')),
                                        Placeholder::make('created_by')
                                            ->label('Dibuat Oleh')
                                            ->content(fn ($record) => $record->creator?->name ?? '-'),
                                    ])
                            ])
                            ->columnSpan(1),
                                
                        Tabs::make('Member')
                            ->tabs([
                                Tabs\Tab::make('Biodata')
                                    ->icon('heroicon-m-user')
                                    ->schema([
                                        Section::make('Identitas Utama')
                                            ->schema([
                                                TextInput::make('nik')
                                                    ->label('NIK')
                                                    ->disabled()
                                                    ->helperText('Nomor Induk Kependudukan Anggota'),

                                                TextInput::make('npwp')
                                                    ->label('NPWP')
                                                    ->disabled()
                                                    ->helperText('Nomor Pokok Wajib Pajak Anggota'),

                                                TextInput::make('full_name')
                                                    ->label('NAMA LENGKAP')
                                                    ->disabled()
                                                    ->helperText('Nama Lengkap sesuai KTP'),

                                                TextInput::make('nickname')
                                                    ->label('NAMA PANGGILAN')
                                                    ->disabled()
                                                    ->helperText('Nama Panggilan Anggota'),
                                            ])
                                            ->columns(2),

                                        Section::make('Data Kelahiran')
                                            ->schema([
                                                TextInput::make('birth_place')
                                                    ->label('TEMPAT LAHIR')
                                                    ->disabled()
                                                    ->helperText('Tempat Lahir Anggota sesuai KTP'),

                                                DatePicker::make('birth_date')
                                                    ->label('TANGGAL LAHIR')
                                                    ->disabled()
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Tanggal Lahir Anggota sesuai KTP'),

                                                TextInput::make('gender')
                                                    ->label('JENIS KELAMIN')
                                                    ->disabled()
                                                    ->helperText('Jenis Kelamin Anggota')
                                                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? 'Pria' : 'Wanita'),

                                                TextInput::make('religion')
                                                    ->label('AGAMA')
                                                    ->disabled()
                                                    ->helperText('Agama Anggota sesuai KTP'),
                                            ])
                                            ->columns(2),

                                        Section::make('Kontak Data')
                                            ->schema([
                                                TextInput::make('telephone_number')
                                                    ->label('NO. TELEPON')
                                                    ->disabled()
                                                    ->helperText('Nomor Telepon Anggota'),

                                                TextInput::make('email')
                                                    ->label('EMAIL')
                                                    ->disabled()
                                                    ->helperText('Email Utama Anggota'),
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
                                                    ->disabled()
                                                    ->helperText('RT Anggota sesuai KTP'),

                                                TextInput::make('RW')
                                                    ->label('RW')
                                                    ->disabled()
                                                    ->helperText('RW Anggota sesuai KTP'),
                                                
                                                TextInput::make('province_name')
                                                    ->label('PROVINSI')
                                                    ->disabled()
                                                    ->helperText('Provinsi Anggota')
                                                    ->formatStateUsing(fn ($record) => 
                                                        $record->province_code ? Province::where('code', $record->province_code)->first()?->name : ''),

                                                TextInput::make('city_name')
                                                    ->label('KOTA/KABUPATEN')
                                                    ->disabled()
                                                    ->helperText('Kota/Kabupaten Anggota')
                                                    ->formatStateUsing(fn ($record) => 
                                                        $record->city_code ? City::where('code', $record->city_code)->first()?->name : ''),

                                                TextInput::make('district_name')
                                                    ->label('KECAMATAN')
                                                    ->disabled()
                                                    ->helperText('Kecamatan Anggota')
                                                    ->formatStateUsing(fn ($record) => 
                                                        $record->district_code ? District::where('code', $record->district_code)->first()?->name : ''),

                                                TextInput::make('village_name')
                                                    ->label('KELURAHAN/DESA')
                                                    ->disabled()
                                                    ->helperText('Kelurahan/Desa Anggota')
                                                    ->formatStateUsing(fn ($record) => 
                                                        $record->village_code ? Village::where('code', $record->village_code)->first()?->name : ''),

                                                TextInput::make('postal_code')
                                                    ->label('KODE POS')
                                                    ->disabled()
                                                    ->helperText('Kode Pos Anggota'),
                                            ])
                                            ->columns(2),
                                            
                                        Textarea::make('full_address')
                                            ->label('ALAMAT LENGKAP')
                                            ->disabled()
                                            ->rows(3)
                                            ->helperText('Alamat Lengkap Anggota sesuai KTP'),
                                    ]),
                                    
                                Tabs\Tab::make('Pekerjaan')
                                    ->icon('heroicon-m-briefcase')
                                    ->schema([
                                        Section::make('Data Pekerjaan')
                                            ->schema([
                                                TextInput::make('occupation')
                                                    ->label('JENIS PEKERJAAN')
                                                    ->disabled()
                                                    ->helperText('Jenis Pekerjaan Anggota')
                                                    ->formatStateUsing(function (string $state): string {
                                                        return match ($state) {
                                                            'pns' => 'PNS',
                                                            'swasta' => 'Karyawan Swasta',
                                                            'wirausaha' => 'Wirausaha',
                                                            'profesional' => 'Profesional',
                                                            'lainnya' => 'Lainnya',
                                                            'tidak_bekerja' => 'Tidak Bekerja',
                                                            default => $state,
                                                        };
                                                    }),

                                                Textarea::make('occupation_description')
                                                    ->label('DESKRIPSI PEKERJAAN')
                                                    ->disabled()
                                                    ->helperText('Deskripsi detail tentang pekerjaan Anggota'),

                                                TextInput::make('income_source')
                                                    ->label('SUMBER PENGHASILAN')
                                                    ->disabled()
                                                    ->helperText('Sumber Penghasilan Utama Anggota')
                                                    ->formatStateUsing(function (string $state): string {
                                                        return match ($state) {
                                                            'gaji' => 'Gaji',
                                                            'usaha' => 'Hasil Usaha',
                                                            'investasi' => 'Hasil Investasi',
                                                            'orangtua' => 'Dari Orang Tua',
                                                            'lainnya' => 'Lainnya',
                                                            default => $state,
                                                        };
                                                    }),

                                                TextInput::make('income_type')
                                                    ->label('KISARAN PENGHASILAN')
                                                    ->disabled()
                                                    ->helperText('Kisaran Penghasilan Bulanan')
                                                    ->formatStateUsing(function (string $state): string {
                                                        return match ($state) {
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
                                                            default => $state,
                                                        };
                                                    }),
                                            ])
                                            ->columns(2),
                                    ]),

                                Tabs\Tab::make('Keluarga')
                                    ->icon('heroicon-m-users')
                                    ->schema([
                                        Section::make('Data Pasangan')
                                            ->description('Data pasangan anggota (jika sudah menikah)')
                                            ->schema([
                                                TextInput::make('spouse_nik')
                                                    ->label('NIK PASANGAN')
                                                    ->disabled()
                                                    ->helperText('Nomor Induk Kependudukan Pasangan'),

                                                TextInput::make('spouse_full_name')
                                                    ->label('NAMA LENGKAP PASANGAN')
                                                    ->disabled()
                                                    ->helperText('Nama Lengkap Pasangan sesuai KTP'),

                                                TextInput::make('spouse_birth_place')
                                                    ->label('TEMPAT LAHIR PASANGAN')
                                                    ->disabled()
                                                    ->helperText('Tempat Lahir Pasangan sesuai KTP'),

                                                DatePicker::make('spouse_birth_date')
                                                    ->label('TANGGAL LAHIR PASANGAN')
                                                    ->disabled()
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Tanggal Lahir Pasangan sesuai KTP'),

                                                TextInput::make('spouse_gender')
                                                    ->label('JENIS KELAMIN PASANGAN')
                                                    ->disabled()
                                                    ->helperText('Jenis Kelamin Pasangan')
                                                    ->formatStateUsing(fn (?string $state): string => 
                                                        $state ? ($state === 'male' ? 'Pria' : 'Wanita') : '-'
                                                    ),

                                                TextInput::make('spouse_telephone_number')
                                                    ->label('NO. TELEPON PASANGAN')
                                                    ->disabled()
                                                    ->helperText('Nomor Telepon Pasangan'),
                                            ])
                                            ->columns(2),

                                        Section::make('Data Ahli Waris')
                                            ->schema([
                                                TextInput::make('heir_relationship')
                                                    ->label('HUBUNGAN DENGAN AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Hubungan Anggota dengan Ahli Waris')
                                                    ->formatStateUsing(function (?string $state): string {
                                                        return match ($state) {
                                                            'suami' => 'Suami',
                                                            'istri' => 'Istri',
                                                            'anak' => 'Anak',
                                                            'orang_tua' => 'Orang Tua',
                                                            'saudara' => 'Saudara',
                                                            'lainnya' => 'Lainnya',
                                                            default => $state ?? '-',
                                                        };
                                                    }),

                                                TextInput::make('heir_nik')
                                                    ->label('NIK AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Nomor Induk Kependudukan Ahli Waris'),

                                                TextInput::make('heir_full_name')
                                                    ->label('NAMA LENGKAP AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Nama Lengkap Ahli Waris sesuai KTP'),

                                                TextInput::make('heir_birth_place')
                                                    ->label('TEMPAT LAHIR AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Tempat Lahir Ahli Waris sesuai KTP'),

                                                DatePicker::make('heir_birth_date')
                                                    ->label('TANGGAL LAHIR AHLI WARIS')
                                                    ->disabled()
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Tanggal Lahir Ahli Waris sesuai KTP'),

                                                TextInput::make('heir_gender')
                                                    ->label('JENIS KELAMIN AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Jenis Kelamin Ahli Waris')
                                                    ->formatStateUsing(fn (?string $state): string => 
                                                        $state ? ($state === 'male' ? 'Pria' : 'Wanita') : '-'
                                                    ),

                                                TextInput::make('heir_telephone')
                                                    ->label('NO. TELEPON AHLI WARIS')
                                                    ->disabled()
                                                    ->helperText('Nomor Telepon Ahli Waris'),
                                            ])
                                            ->columns(2),
                                    ]),

                                Tabs\Tab::make('Dokumen')
                                    ->icon('heroicon-m-document')
                                    ->schema([
                                        Section::make('Dokumen Anggota')
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('documents')
                                                    ->label('Dokumen Anggota')
                                                    ->collection('documents')
                                                    ->multiple()
                                                    ->downloadable()
                                                    ->openable()
                                                    ->disabled()
                                                    ->helperText('Dokumen yang telah diunggah oleh anggota')
                                                    ->columns(1),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(2)
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ubah Data Anggota')
                ->icon('heroicon-m-pencil-square')
                ->color('primary')
                ->tooltip('Edit data hanya tersedia untuk anggota aktif')
                ->visible(fn ($record) => $record->member_status === 'active'),
            Action::make('activateMember')
                ->label('Aktifkan Anggota')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalDescription('Pastikan simpanan pokok dan wajib anggota sudah aktif sebelum melanjutkan.')
                ->modalSubmitActionLabel('Ya, Aktifkan')
                ->modalCancelActionLabel('Batal')
                ->visible(fn ($record) => $record->member_status === 'pending')
                ->action(function () {
                    $this->record->member_status = 'active';
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Anggota berhasil diaktifkan')
                        ->success()
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}