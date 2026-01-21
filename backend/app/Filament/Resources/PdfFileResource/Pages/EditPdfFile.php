<?php

namespace App\Filament\Resources\PdfFileResource\Pages;

use App\Filament\Resources\PdfFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPdfFile extends EditRecord
{
    protected static string $resource = PdfFileResource::class;

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
