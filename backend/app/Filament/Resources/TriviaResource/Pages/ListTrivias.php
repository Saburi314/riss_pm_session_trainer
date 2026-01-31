<?php

namespace App\Filament\Resources\TriviaResource\Pages;

use App\Filament\Resources\TriviaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrivias extends ListRecords
{
    protected static string $resource = TriviaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
