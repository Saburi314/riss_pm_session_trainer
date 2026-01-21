<?php

namespace App\Filament\Resources\PdfFileResource\Pages;

use App\Filament\Resources\PdfFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPdfFiles extends ListRecords
{
    protected static string $resource = PdfFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('batchImport')
                ->label('過去問PDFを一括でDBへ登録')
                ->tooltip('raw_pdfsフォルダ内のPDFをスキャンして新着分をDBに登録します。')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('ディレクトリからのインポート')
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
            Actions\Action::make('syncVectorStore')
                ->label('ベクトルストアと一括同期')
                ->tooltip('DBの未同期ファイルをAIエンジン（Vector Store）に転送します。')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('AIベクトルストアへの同期')
                ->modalDescription('まだAIへ送信されていないファイルをすべて転送します。')
                ->action(function () {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('vs:sync');

                    if ($exitCode === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('AI同期完了')
                            ->body('ファイルがAIに認識されるようになりました。')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('AI同期失敗')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
