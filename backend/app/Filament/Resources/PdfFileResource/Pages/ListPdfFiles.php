<?php

namespace App\Filament\Resources\PdfFileResource\Pages;

use App\Filament\Resources\PdfFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPdfFiles extends ListRecords
{
    protected static string $resource = PdfFileResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PdfFileResource\Widgets\PdfFileSummary::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('batchImport')
                ->label('過去問PDFを一括でDBへ登録')
                ->tooltip('raw_pdfsフォルダ内のPDFをスキャンして新着分をDBに登録します。')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('原本ディレクトリからのインポート')
                ->modalDescription('storage/app/raw_pdfs ディレクトリ内の全PDFをスキャンして登録します。既に登録済みのPDFはスキップされます。')
                ->action(function () {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('pdf:batch-import', [
                        'directory' => storage_path('app/raw_pdfs'),
                        '--force' => true,
                    ]);

                    if ($exitCode === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('スキャン完了')
                            ->body('新しいPDFがデータベースに登録されました。')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('スキャン失敗')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
