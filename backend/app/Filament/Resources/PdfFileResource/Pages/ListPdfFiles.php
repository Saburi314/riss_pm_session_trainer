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
            Actions\CreateAction::make()
                ->label('過去問PDFを個別に登録')
                ->icon('heroicon-o-plus'),
            Actions\Action::make('batchImport')
                ->label('過去問PDFを一括登録')
                ->tooltip('raw_pdfsフォルダ内のPDFをスキャンして新着分を登録します。')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('サーバーディレクトリからのインポート')
                ->modalDescription('storage/app/raw_pdfs ディレクトリ内の全PDFをスキャンして登録します。既に登録済みのファイルはスキップされます。')
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
                ->label('ベクターストアと同期')
                ->tooltip('DBの未同期ファイルをAIエンジン（Vector Store）に転送します。')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('AIベクターストアへの同期')
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
            Actions\Action::make('initVectorStore')
                ->label('新規ベクターストア作成')
                ->tooltip('OpenAI側に新しいAIデータ保存領域（Vector Store）を作成します。')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('新しいAIストアの作成')
                ->modalDescription('OpenAI側に新しいVector Storeを作成します。作成されたIDは通知されます。')
                ->action(function () {
                    $output = new \Symfony\Component\Console\Output\BufferedOutput();
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('vs:init', [], $output);

                    $message = $output->fetch();

                    if ($exitCode === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('作成成功')
                            ->body("新しいストアが作成されました：\n" . $message)
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('作成失敗')
                            ->body($message)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
