<?php

namespace App\Filament\Resources\SecurityTriviaResource\Pages;

use App\Filament\Resources\SecurityTriviaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecurityTrivia extends ListRecords
{
    protected static string $resource = SecurityTriviaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
