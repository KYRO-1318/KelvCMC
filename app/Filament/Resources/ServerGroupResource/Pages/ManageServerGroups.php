<?php

namespace App\Filament\Resources\ServerGroupResource\Pages;

use App\Filament\Resources\ServerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServerGroups extends ManageRecords
{
    protected static string $resource = ServerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
