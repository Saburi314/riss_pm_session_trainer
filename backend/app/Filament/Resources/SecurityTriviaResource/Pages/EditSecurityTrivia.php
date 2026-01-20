<?php

namespace App\Filament\Resources\SecurityTriviaResource\Pages;

use App\Filament\Resources\SecurityTriviaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecurityTrivia extends EditRecord
{
    protected static string $resource = SecurityTriviaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
