<?php

namespace App\Filament\Resources\PdfFileResource\Pages;

use App\Filament\Resources\PdfFileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePdfFile extends CreateRecord
{
    protected static string $resource = PdfFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $filePath = storage_path('app/' . $data['storage_path']);

        if (file_exists($filePath)) {
            // サイズを自動取得
            $data['size'] = filesize($filePath);

            // ファイル名が空の場合は自動取得
            if (empty($data['filename'])) {
                $data['filename'] = basename($data['storage_path']);
            }
        }

        return $data;
    }
}
