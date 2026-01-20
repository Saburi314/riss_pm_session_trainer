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
            Actions\CreateAction::make(),
            Actions\Action::make('batchImport')
                ->label('サーバーから一括インポート')
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
                            ->title('インポート完了')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('インポート失敗')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('syncVectorStore')
                ->label('OpenAIと同期')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('DBに登録済みで、まだOpenAIにアップロードされていないファイルを同期します。')
                ->action(function () {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('vs:sync');

                    if ($exitCode === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('同期完了')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('同期失敗')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('initVectorStore')
                ->label('新規VectorStore作成')
                ->icon('heroicon-o-plus-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('新しいVector Storeの作成')
                ->modalDescription('OpenAI側に新しいVector Storeを作成します。実行後、表示されるIDを.envに手動で設定する必要があります。よろしいですか？')
                ->action(function () {
                    $output = new \Symfony\Component\Console\Output\BufferedOutput();
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('vs:init', [], $output);

                    $message = $output->fetch();

                    if ($exitCode === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('作成成功')
                            ->body($message)
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
