<?php

namespace App\Filament\Resources\TriviaResource\Pages;

use App\Filament\Resources\TriviaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTrivia extends CreateRecord
{
    protected static string $resource = TriviaResource::class;
}
