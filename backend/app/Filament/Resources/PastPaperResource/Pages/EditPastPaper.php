<?php

namespace App\Filament\Resources\PastPaperResource\Pages;

use App\Filament\Resources\PastPaperResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPastPaper extends EditRecord
{
    protected static string $resource = PastPaperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ファイルが変更されていない場合（storage_path が空）、既存の値を保持
        if (empty($data['storage_path'])) {
            unset($data['storage_path']);
        }

        return $data;
    }
}
