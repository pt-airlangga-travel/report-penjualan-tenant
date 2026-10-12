<?php

namespace App\Filament\Resources\DailySalesReports;

use App\Filament\Resources\DailySalesReports\Pages\ManageDailySalesReports;
use App\Models\DailySalesReport;
use App\Models\Kantin;
use App\Models\Tenant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use UnitEnum;

class DailySalesReportResource extends Resource
{
    protected static ?string $model = DailySalesReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $modelLabel = 'Rekapitulasi Harian';

    protected static ?string $pluralModelLabel = 'Rekapitulasi Harian';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAbility('sales.view_all') || $user->hasAbility('sales.view_own'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Subquery: agregasi per tanggal + kantin + tenant (granularitas terkecil),
     * supaya filter bisa diterapkan di query luar.
     * Query luar: filter lalu group by tanggal => 1 baris per hari.
     */
    public static function getEloquentQuery(): Builder
    {
        $sub = DB::table('sales_details')
            ->selectRaw('
                DATE(sales_imports.period_start) as report_date,
                sales_imports.kantin_id as kantin_id,
                sales_details.tenant_id as tenant_id,
                SUM(sales_details.qty) as total_qty,
                SUM(sales_details.gross_sales) as total_gross,
                SUM(sales_details.discount) as total_discount,
                SUM(sales_details.grand_total) as total_grand,
                SUM(sales_details.grand_total * (1 - COALESCE(tenants.fee_percentage, 0) / 100)) as total_bersih
            ')
            ->join('sales_imports', 'sales_details.sales_import_id', '=', 'sales_imports.id')
            ->leftJoin('tenants', 'sales_details.tenant_id', '=', 'tenants.id')
            ->groupBy(
                DB::raw('DATE(sales_imports.period_start)'),
                'sales_imports.kantin_id',
                'sales_details.tenant_id'
            );

        $user = auth()->user();
        if ($user && ! $user->hasAbility('sales.view_all')) {
            $sub->where('sales_details.tenant_id', $user->tenant_id ?? 0);
        }

        return DailySalesReport::query()
            ->fromSub($sub, 'daily_sales_reports')
            ->selectRaw('
                report_date,
                SUM(total_qty) as total_qty,
                SUM(total_gross) as total_gross,
                SUM(total_discount) as total_discount,
                SUM(total_grand) as total_grand,
                SUM(total_bersih) as total_bersih
            ')
            ->groupBy('report_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        $canViewAll = auth()->user()?->hasAbility('sales.view_all') ?? false;

        $rupiah = fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.');

        return $table
            ->description('Menampilkan rekapitulasi penjualan per hari. Gunakan filter periode untuk melihat tanggal lain.')
            ->columns([
                TextColumn::make('report_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('total_qty')
                    ->label('Total Qty Terjual')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Qty')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                    ),

                TextColumn::make('total_gross')
                    ->label('Total Gross Sales')
                    ->formatStateUsing($rupiah)
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_discount')
                    ->label('Total Diskon')
                    ->formatStateUsing($rupiah)
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_grand')
                    ->label('Total Grand Total (Net)')
                    ->formatStateUsing($rupiah)
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->visible($canViewAll)
                    ->summarize(
                        Sum::make()
                            ->label('Total Omzet')
                            ->formatStateUsing($rupiah)
                    ),

                TextColumn::make('total_bersih')
                    ->label($canViewAll ? 'Total Setelah Fee' : 'Total Penjualan Bersih')
                    ->formatStateUsing($rupiah)
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->summarize(
                        Sum::make()
                            ->label('Total Keseluruhan Bersih')
                            ->formatStateUsing($rupiah)
                    ),
            ])
            ->filters([
                SelectFilter::make('kantin_id')
                    ->label('Pilih Kantin')
                    ->options(fn () => Kantin::pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $value) => $q->where('kantin_id', $value)
                    ))
                    ->visible($canViewAll),

                SelectFilter::make('tenant_id')
                    ->label('Filter Tenant')
                    ->options(fn () => Tenant::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $value) => $q->where('tenant_id', $value)
                    ))
                    ->visible($canViewAll),

                DateRangeFilter::make('period_date')
                    ->label('Periode Tanggal')
                    ->default(now()->startOfMonth()->format('d/m/Y') . ' - ' . now()->endOfMonth()->format('d/m/Y'))
                    ->query(function (Builder $query, array $data): Builder {
                        $range = $data['period_date'] ?? null;

                        if (! $range) {
                            return $query;
                        }

                        $dates = explode(' - ', $range);

                        if (count($dates) !== 2) {
                            return $query;
                        }

                        try {
                            $start = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                            $end = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                        } catch (\Throwable $e) {
                            $start = Carbon::parse(trim($dates[0]))->startOfDay();
                            $end = Carbon::parse(trim($dates[1]))->endOfDay();
                        }

                        return $query->whereBetween('report_date', [
                            $start->toDateString(),
                            $end->toDateString(),
                        ]);
                    }),
            ])
            ->filtersFormColumns(3)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('report_date', 'asc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDailySalesReports::route('/'),
        ];
    }
}