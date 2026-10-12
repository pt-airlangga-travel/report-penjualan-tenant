<?php

namespace App\Filament\Resources\TargetBulanan\Pages;

use App\Filament\Resources\TargetBulanan\TargetBulananResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTargetBulanan extends ManageRecords
{
    protected static string $resource = TargetBulananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Target Bulanan')
                ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->hasAbility('target.create')),
        ];
    }
}
