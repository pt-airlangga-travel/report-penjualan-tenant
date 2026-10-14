<?php

namespace App\Filament\Resources\BerkasTenant\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class BerkasTenantStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $total = Tenant::count();

        $incompleteQuery = fn (): Builder => Tenant::query()->where(function ($q) {
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

        $incomplete = $incompleteQuery()->count();
        $complete = $total - $incomplete;

        return [
            Stat::make('Total Tenant', (string) $total)
                ->description('Terdaftar di sistem')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
            Stat::make('Berkas Lengkap', (string) $complete)
                ->description('Siap diverifikasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Belum Lengkap', (string) $incomplete)
                ->description('Perlu dilengkapi tenant')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
