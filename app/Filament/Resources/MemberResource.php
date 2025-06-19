<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers\SavingsRelationManager;
use App\Filament\Resources\MemberResource\Widgets\MemberStatsWidget;
use App\Filament\Resources\MemberResource\Widgets\MemberGrowthChart;
use App\Filament\Resources\MemberResource\Widgets\MemberStatusPieChart;
use App\Models\Member;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;


class MemberResource extends Resource
{
    protected static ?string $navigationGroup = 'Data Master';
    
    protected static ?string $model = Member::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $pluralLabel = 'Anggota Koperasi';

    protected static ?int $navigationSort = 1;  // Add this line to make it appear first

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
            ])
            ->columns([
                TextColumn::make('member_id')
                    ->label('ID Anggota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telephone_number')
                    ->label('No. Telepon')
                    ->searchable()
                    ->sortable(),

                // Modern approach: Use TextColumn with badge() method
                TextColumn::make('member_status')
                    ->label('Status Anggota')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Dalam Proses',
                        'active' => 'Aktif',
                        'delinquent' => 'Bermasalah',
                        'terminated' => 'Keluar',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'terminated' => 'danger',
                        'pending' => 'warning',
                        'delinquent' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
            ])
            ->filters([
                // Filter Status Anggota
                Tables\Filters\SelectFilter::make('member_status')
                    ->label('Status Keanggotaan')
                    ->options([
                        'pending' => 'Dalam Proses',
                        'active' => 'Aktif',
                        'delinquent' => 'Bermasalah',
                        'terminated' => 'Keluar',
                    ])
                    ->placeholder('Semua Status'),

                // Filter Jenis Kelamin
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Pria',
                        'female' => 'Wanita',
                    ])
                    ->placeholder('Semua Gender'),

                // Filter Jenis Pekerjaan
                Tables\Filters\SelectFilter::make('occupation')
                    ->label('Jenis Pekerjaan')
                    ->options([
                        'pns' => 'PNS',
                        'swasta' => 'Karyawan Swasta',
                        'wirausaha' => 'Wirausaha',
                        'profesional' => 'Profesional',
                        'lainnya' => 'Lainnya',
                    ])
                    ->placeholder('Semua Pekerjaan'),

                // Filter Provinsi
                Tables\Filters\SelectFilter::make('province_code')
                    ->label('Provinsi')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->placeholder('Semua Provinsi'),

                // Filter Kota/Kabupaten
                Tables\Filters\SelectFilter::make('city_code')
                    ->label('Kota/Kabupaten')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->placeholder('Semua Kota'),

                // Filter Rentang Usia
                Tables\Filters\Filter::make('age_range')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('age_from')
                                    ->label('Usia Minimum')
                                    ->numeric()
                                    ->placeholder('18'),
                                \Filament\Forms\Components\TextInput::make('age_to')
                                    ->label('Usia Maksimum')
                                    ->numeric()
                                    ->placeholder('65'),
                            ]),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['age_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereRaw('DATEDIFF(NOW(), birth_date) / 365 >= ?', [$date]),
                            )
                            ->when(
                                $data['age_to'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereRaw('DATEDIFF(NOW(), birth_date) / 365 <= ?', [$date]),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['age_from'] ?? null) {
                            $indicators['age_from'] = 'Usia min: ' . $data['age_from'];
                        }
                        if ($data['age_to'] ?? null) {
                            $indicators['age_to'] = 'Usia max: ' . $data['age_to'];
                        }
                        return $indicators;
                    }),

                // Filter Tanggal Bergabung
                Tables\Filters\Filter::make('join_date_range')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('join_from')
                                    ->label('Bergabung Dari')
                                    ->placeholder('DD/MM/YYYY'),
                                \Filament\Forms\Components\DatePicker::make('join_until')
                                    ->label('Bergabung Sampai')
                                    ->placeholder('DD/MM/YYYY'),
                            ]),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['join_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['join_until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['join_from'] ?? null) {
                            $indicators['join_from'] = 'Bergabung dari: ' . \Carbon\Carbon::parse($data['join_from'])->format('d/m/Y');
                        }
                        if ($data['join_until'] ?? null) {
                            $indicators['join_until'] = 'Bergabung sampai: ' . \Carbon\Carbon::parse($data['join_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                // Filter Kisaran Penghasilan
                Tables\Filters\SelectFilter::make('income_type')
                    ->label('Kisaran Penghasilan')
                    ->options([
                        'harian_rendah' => 'Harian: < Rp 200K',
                        'harian_sedang' => 'Harian: Rp 200K - 500K',
                        'harian_tinggi' => 'Harian: > Rp 500K',
                        'mingguan_rendah' => 'Mingguan: < Rp 1Jt',
                        'mingguan_sedang' => 'Mingguan: Rp 1Jt - 3Jt',
                        'mingguan_tinggi' => 'Mingguan: > Rp 3Jt',
                        'bulanan_rendah' => 'Bulanan: < Rp 5Jt',
                        'bulanan_sedang' => 'Bulanan: Rp 5Jt - 10Jt',
                        'bulanan_tinggi' => 'Bulanan: Rp 10Jt - 20Jt',
                        'bulanan_sangat_tinggi' => 'Bulanan: > Rp 20Jt',
                        'tahunan_rendah' => 'Tahunan: < Rp 60Jt',
                        'tahunan_sedang' => 'Tahunan: Rp 60Jt - 120Jt',
                        'tahunan_tinggi' => 'Tahunan: Rp 120Jt - 240Jt',
                        'tahunan_sangat_tinggi' => 'Tahunan: > Rp 240Jt',
                    ])
                    ->placeholder('Semua Penghasilan'),
            ], layout: \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(3) // Set the number of columns in modal
            ->filtersTriggerAction(
                fn (\Filament\Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-m-funnel')
            )
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->iconButton(),

                // Tables\Actions\EditAction::make('edit')
                //     ->label('Ubah')
                //     ->icon('heroicon-m-pencil-square')
                //     ->iconButton(),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                // Tambahkan bulk actions jika diperlukan
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            
        ]);
    }

    public static function getRelations(): array
    {
        return [
            SavingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'),
        ];
    }

    // Tambahkan method untuk menampilkan widgets
    public static function getWidgets(): array
    {
        return [
            MemberStatsWidget::class,
            // MemberStatusPieChart::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
    return (string) \App\Models\Member::count();
    }
}