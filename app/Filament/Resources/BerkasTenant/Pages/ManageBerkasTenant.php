<?php

namespace App\Filament\Resources\BerkasTenant\Pages;

use App\Filament\Resources\BerkasTenant\BerkasTenantResource;
use App\Filament\Resources\BerkasTenant\Widgets\BerkasTenantStatsWidget;
use Filament\Resources\Pages\ManageRecords;

class ManageBerkasTenant extends ManageRecords
{
    protected static string $resource = BerkasTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BerkasTenantStatsWidget::class,
        ];
    }
}
