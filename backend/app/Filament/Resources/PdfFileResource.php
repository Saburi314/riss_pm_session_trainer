<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfFileResource\Pages;
use App\Filament\Resources\PdfFileResource\RelationManagers;
use App\Models\PdfFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PdfFileResource extends Resource
{
    protected static ?string $model = PdfFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'PDFソース管理';
    protected static ?string $modelLabel = 'PDFファイル';
    protected static ?string $pluralModelLabel = 'PDFソース管理';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PDFファイル情報')
                    ->schema([
                        Forms\Components\TextInput::make('filename')
                            ->label('ファイル名')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('空欄の場合はファイル名が使用されます'),
                        Forms\Components\FileUpload::make('storage_path')
                            ->label('PDFファイル')
                            ->disk('local')
                            ->directory('private/pdfs/manual') // 手動アップロード用
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->helperText('編集時にファイルを変更しない場合、既存のファイルが保持されます。'),
                        Forms\Components\TextInput::make('storage_disk')
                            ->label('ストレージディスク')
                            ->required()
                            ->maxLength(255)
                            ->default('local'),
                        Forms\Components\Hidden::make('size')
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('メタデータ')
                    ->schema([
                        Forms\Components\TextInput::make('year')
                            ->label('年')
                            ->required()
                            ->numeric(),
                        Forms\Components\Select::make('season')
                            ->label('時期')
                            ->options([
                                'spring' => '春',
                                'autumn' => '秋',
                                'special' => '特別',
                            ])
                            ->required(),
                        Forms\Components\Select::make('exam_period')
                            ->label('試験区分')
                            ->options([
                                'am2' => '午前II',
                                'pm1' => '午後　午後１',
                                'pm2' => '午後　午後２',
                                'pm' => '午後',
                            ])
                            ->required(),
                        Forms\Components\Select::make('doc_type')
                            ->label('資料種別')
                            ->options([
                                'question' => '問題',
                                'answer' => '解答',
                                'commentary' => '解説',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('同期ステータス')
                    ->schema([
                        Forms\Components\TextInput::make('openai_file_id')
                            ->label('OpenAIファイルID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vector_store_file_id')
                            ->label('ベクトルストアファイルID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('index_status')
                            ->label('インデックス状況')
                            ->disabled()
                            ->maxLength(20)
                            ->default('pending'),
                        Forms\Components\DateTimePicker::make('indexed_at')
                            ->label('インデックス日時')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Textarea::make('error_message')
                    ->label('エラーメッセージ')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('ファイル名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('storage_path')
                    ->label('パス')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('size')
                    ->label('サイズ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('year')
                    ->label('年')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season')
                    ->label('時期')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'spring' => '春',
                        'autumn' => '秋',
                        'special' => '特別',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('exam_period')
                    ->label('試験区分')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'am2' => '午前II',
                        'pm1' => '午後　午後１',
                        'pm2' => '午後　午後２',
                        'pm' => '午後',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('doc_type')
                    ->label('資料種別')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'question' => '問題',
                        'answer' => '解答',
                        'commentary' => '解説',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('index_status')
                    ->label('状況')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('indexed_at')
                    ->label('完了日')
                    ->dateTime()
                    ->sortable(),
            ])
            ->groups([
                Tables\Grouping\Group::make('year')
                    ->label('年度')
                    ->collapsible(),
            ])
            ->defaultGroup('year')
            ->filters([
                Tables\Filters\SelectFilter::make('index_status')
                    ->label('状況')
                    ->options([
                        'pending' => '未処理',
                        'completed' => '完了',
                        'failed' => '失敗',
                        'in_progress' => '処理中',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label('ベクトルストアと同期')
                    ->tooltip('このファイルをAIが読み込めるように（Vector Storeへ）転送します。')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->visible(fn(\App\Models\PdfFile $record): bool => $record->index_status !== 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('ベクトルストア同期の実行')
                    ->modalDescription('このファイルをOpenAIのベクトルストアに送信し、AIが検索・利用できる状態にします。')
                    ->action(function (\App\Models\PdfFile $record, \App\Services\VectorStoreService $service): void {
                        try {
                            set_time_limit(600); // 10分まで実行を許可
                            $service->syncFile($record);
                            \Filament\Notifications\Notification::make()
                                ->title('同期成功')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('同期失敗')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('syncSelected')
                        ->label('選択したファイルをベクトルストアと同期')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records, \App\Services\VectorStoreService $service): void {
                            set_time_limit(0); // 無制限
                            $records->each(function ($record) use ($service) {
                                if ($record->index_status !== 'completed') {
                                    $service->syncFile($record);
                                }
                            });
                            \Filament\Notifications\Notification::make()
                                ->title('選択したファイルの同期が完了しました')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPdfFiles::route('/'),
            'edit' => Pages\EditPdfFile::route('/{record}/edit'),
        ];
    }
}
