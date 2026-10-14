<?php

namespace App\Filament\Resources\BerkasTenant;

use App\Filament\Resources\BerkasTenant\Pages\ManageBerkasTenant;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BerkasTenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Data Tenant';

    protected static ?string $navigationLabel = 'Upload Berkas';

    protected static ?string $modelLabel = 'Berkas Tenant';

    protected static ?string $pluralModelLabel = 'Berkas & Dokumen Tenant';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canView(mixed $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Tenant & Pemilik')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Tenant')
                            ->disabled(),
                        TextInput::make('owner_name')
                            ->label('Nama Pemilik')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('nik')
                            ->label('NIK Pemilik')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('no_kk')
                            ->label('No. KK')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('No. HP / WA')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('birth_place_date')
                            ->label('Tempat, Tgl Lahir (TTL)')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        TextInput::make('nomor_halal')
                            ->label('No. Sertifikat Halal')
                            ->placeholder('Belum diisi')
                            ->disabled(),
                        Textarea::make('address')
                            ->label('Alamat Pemilik')
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),

                Section::make('Berkas & Dokumen (Pratinjau Gambar)')
                    ->schema([
                        FileUpload::make('file_ktp')
                            ->label('Scan / Foto KTP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_kk')
                            ->label('Scan / Foto KK')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_npwp')
                            ->label('Scan / Foto NPWP')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                        FileUpload::make('file_sertifikat_higenitas')
                            ->label('Foto Sertifikat Layak Higenitas')
                            ->image()
                            ->directory('tenant-documents')
                            ->visibility('public')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        // Tampilan read-only untuk admin: identitas + pratinjau dokumen.
        return $schema
            ->components([
                Section::make('Identitas Tenant & Pemilik')
                    ->schema([
                        TextEntry::make('name')->label('Nama Tenant'),
                        TextEntry::make('kantin.name')->label('Kantin')->placeholder('—'),
                        TextEntry::make('owner_name')->label('Nama Pemilik')->placeholder('Belum diisi'),
                        TextEntry::make('nik')->label('NIK Pemilik')->placeholder('Belum diisi'),
                        TextEntry::make('no_kk')->label('No. KK')->placeholder('Belum diisi'),
                        TextEntry::make('phone')->label('No. HP / WA')->placeholder('Belum diisi'),
                        TextEntry::make('birth_place_date')->label('Tempat, Tgl Lahir (TTL)')->placeholder('Belum diisi'),
                        TextEntry::make('nomor_halal')->label('No. Sertifikat Halal')->placeholder('Belum diisi'),
                        TextEntry::make('address')->label('Alamat Pemilik')->placeholder('Belum diisi')->columnSpanFull(),
                        TextEntry::make('document_status')->label('Status Kelengkapan')->badge()
                            ->color(fn ($state) => $state === 'Lengkap' ? 'success' : 'danger'),
                    ])->columns(2),

                Section::make('Berkas & Dokumen (Pratinjau Gambar)')
                    ->description('Klik gambar untuk membuka ukuran penuh di tab baru.')
                    ->schema([
                        ImageEntry::make('file_ktp')
                            ->label('Scan / Foto KTP')
                            ->disk('public')
                            ->placeholder('Belum diunggah')
                            ->openUrlInNewTab(),
                        ImageEntry::make('file_kk')
                            ->label('Scan / Foto KK')
                            ->disk('public')
                            ->placeholder('Belum diunggah')
                            ->openUrlInNewTab(),
                        ImageEntry::make('file_npwp')
                            ->label('Scan / Foto NPWP')
                            ->disk('public')
                            ->placeholder('Belum diunggah (opsional)')
                            ->openUrlInNewTab(),
                        ImageEntry::make('file_sertifikat_higenitas')
                            ->label('Foto Sertifikat Layak Higienitas')
                            ->disk('public')
                            ->placeholder('Belum diunggah')
                            ->openUrlInNewTab(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kantin.name')
                    ->label('Kantin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('owner_name')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('No. HP / WA')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi')
                    ->toggleable(),
                TextColumn::make('nomor_halal')
                    ->label('No. Halal')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum diisi')
                    ->toggleable(),
                IconColumn::make('file_ktp')
                    ->label('KTP')
                    ->boolean()
                    ->state(fn (Tenant $record) => filled($record->file_ktp))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
                IconColumn::make('file_kk')
                    ->label('KK')
                    ->boolean()
                    ->state(fn (Tenant $record) => filled($record->file_kk))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
                IconColumn::make('file_npwp')
                    ->label('NPWP')
                    ->boolean()
                    ->state(fn (Tenant $record) => filled($record->file_npwp))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(),
                IconColumn::make('file_sertifikat_higenitas')
                    ->label('Higienitas')
                    ->boolean()
                    ->state(fn (Tenant $record) => filled($record->file_sertifikat_higenitas))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
                IconColumn::make('identitas')
                    ->label('Identitas')
                    ->boolean()
                    ->state(fn (Tenant $record) => filled($record->owner_name)
                        && filled($record->nik)
                        && filled($record->no_kk)
                        && filled($record->phone)
                        && filled($record->address)
                        && filled($record->birth_place_date)
                        && filled($record->nomor_halal))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
                TextColumn::make('document_status')
                    ->label('Status Berkas')
                    ->state(fn (Tenant $record) => $record->document_status)
                    ->badge()
                    ->color(fn ($state) => $state === 'Lengkap' ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Filter Kantin')
                    ->relationship('kantin', 'name'),
                SelectFilter::make('status')
                    ->label('Status Kelengkapan Berkas')
                    ->options([
                        'lengkap' => 'Berkas Lengkap',
                        'belum_lengkap' => 'Berkas Belum Lengkap',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (($data['value'] ?? null) === 'lengkap') {
                            $query->whereNotNull('owner_name')->where('owner_name', '!=', '')
                                ->whereNotNull('nik')->where('nik', '!=', '')
                                ->whereNotNull('no_kk')->where('no_kk', '!=', '')
                                ->whereNotNull('phone')->where('phone', '!=', '')
                                ->whereNotNull('address')->where('address', '!=', '')
                                ->whereNotNull('birth_place_date')->where('birth_place_date', '!=', '')
                                ->whereNotNull('nomor_halal')->where('nomor_halal', '!=', '')
                                ->whereNotNull('file_ktp')->where('file_ktp', '!=', '')
                                ->whereNotNull('file_kk')->where('file_kk', '!=', '')
                                ->whereNotNull('file_sertifikat_higenitas')->where('file_sertifikat_higenitas', '!=', '');
                        } elseif (($data['value'] ?? null) === 'belum_lengkap') {
                            $query->where(function ($q) {
                                $q->whereNull('owner_name')->orWhere('owner_name', '')
                                    ->orWhereNull('nik')->orWhere('nik', '')
                                    ->orWhereNull('no_kk')->orWhere('no_kk', '')
                                    ->orWhereNull('phone')->orWhere('phone', '')
                                    ->orWhereNull('address')->orWhere('address', '')
                                    ->orWhereNull('birth_place_date')->orWhere('birth_place_date', '')
                                    ->orWhereNull('nomor_halal')->orWhere('nomor_halal', '')
                                    ->orWhereNull('file_ktp')->orWhere('file_ktp', '')
                                    ->orWhereNull('file_kk')->orWhere('file_kk', '')
                                    ->orWhereNull('file_sertifikat_higenitas')->orWhere('file_sertifikat_higenitas', '');
                            });
                        }
                    }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Lihat Berkas')
                    ->modalHeading('Rincian & Pratinjau Berkas Tenant')
                    ->modalWidth('7xl'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBerkasTenant::route('/'),
        ];
    }
}
