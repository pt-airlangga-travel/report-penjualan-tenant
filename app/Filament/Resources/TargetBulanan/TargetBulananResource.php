<?php

namespace App\Filament\Resources\TargetBulanan;

use App\Filament\Resources\TargetBulanan\Pages\ManageTargetBulanan;
use App\Models\SalesDetail;
use App\Models\Tenant;
use App\Models\TenantTarget;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class TargetBulananResource extends Resource
{
    protected static ?string $model = TenantTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan & Target';

    protected static ?string $slug = 'target-bulanan';

    protected static ?string $modelLabel = 'Target Bulanan';

    protected static ?string $pluralModelLabel = 'Target Bulanan Tenant';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasAbility('target.view_any') || $user->hasAbility('target.view'));
    }

    public static function canCreate(): bool
    {
        return static::isAdminUser();
    }

    public static function canEdit(mixed $record): bool
    {
        return static::isAdminUser();
    }

    public static function canDelete(mixed $record): bool
    {
        return static::isAdminUser();
    }

    protected static function isAdminUser(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $user->hasAbility('target.create') || $user->hasAbility('target.update');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['tenant.kantin']);
        $user = auth()->user();

        if ($user && $user->isTenant() && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $currentYear = (int) now()->year;
        $years = range($currentYear - 2, $currentYear + 2);
        $yearOptions = array_combine($years, $years);

        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('month')
                    ->label('Bulan')
                    ->options(TenantTarget::MONTH_NAMES)
                    ->default((int) now()->month)
                    ->required(),

                Select::make('year')
                    ->label('Tahun')
                    ->options($yearOptions)
                    ->default($currentYear)
                    ->required()
                    ->unique(
                        table: 'tenant_targets',
                        column: 'year',
                        modifyRuleUsing: function (Unique $rule, callable $get, ?TenantTarget $record) {
                            $rule->where('tenant_id', $get('tenant_id'))
                                 ->where('month', $get('month'));

                            return $record ? $rule->ignore($record->id) : $rule;
                        }
                    ),

                TextInput::make('target_amount')
                    ->label('Jumlah Target')
                    ->prefix('Rp')
                    ->numeric()
                    ->required()
                    ->helperText('Nominal target omzet penjualan untuk bulan ini'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $canAdmin = static::isAdminUser();
        $currentYear = (int) now()->year;
        $years = range($currentYear - 2, $currentYear + 2);

        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Nama Tenant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('tenant.kantin.name')
                    ->label('Kantin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('period_label')
                    ->label('Bulan & Tahun')
                    ->state(fn (TenantTarget $record) => $record->period_label)
                    ->weight('semibold'),

                TextColumn::make('target_amount')
                    ->label('Target')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('actual_sales')
                    ->label('Penjualan Riil')
                    ->state(fn (TenantTarget $record) => static::calculateActualSales($record))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->weight('bold')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('achievement')
                    ->label('Achievement (%)')
                    ->state(function (TenantTarget $record) {
                        $actual = static::calculateActualSales($record);
                        $target = (float) $record->target_amount;
                        return $target > 0 ? ($actual / $target) * 100 : 0;
                    })
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1, ',', '.') . '%')
                    ->badge()
                    ->color(function ($state) {
                        $val = (float) $state;
                        if ($val >= 100) return 'success';
                        if ($val >= 75) return 'warning';
                        return 'danger';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (TenantTarget $record) {
                        $actual = static::calculateActualSales($record);
                        $target = (float) $record->target_amount;
                        return $actual >= $target ? 'Melebihi Target' : 'Belum Tercapai';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Melebihi Target' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Filter Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('month')
                    ->label('Filter Bulan')
                    ->options(TenantTarget::MONTH_NAMES),

                SelectFilter::make('year')
                    ->label('Filter Tahun')
                    ->options(array_combine($years, $years)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->visible($canAdmin),
                DeleteAction::make()
                    ->label('Hapus')
                    ->visible($canAdmin),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTargetBulanan::route('/'),
        ];
    }

    public static function calculateActualSales(TenantTarget $target): float
    {
        $start = Carbon::create($target->year, $target->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return (float) SalesDetail::where('tenant_id', $target->tenant_id)
            ->whereHas('salesImport', function (Builder $q) use ($start, $end) {
                $q->whereDate('period_end', '>=', $start->toDateString())
                  ->whereDate('period_start', '<=', $end->toDateString());
            })
            ->sum('grand_total');
    }
}
