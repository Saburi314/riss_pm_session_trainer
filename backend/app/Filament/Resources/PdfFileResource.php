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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PdfFileResource extends Resource
{
    protected static ?string $model = PdfFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'PDFソース管理';
    protected static ?string $modelLabel = 'PDFファイル';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PDFファイル情報')
                    ->schema([
                        Forms\Components\TextInput::make('filename')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('空欄の場合はファイル名が使用されます'),
                        Forms\Components\FileUpload::make('storage_path')
                            ->label('PDFファイル')
                            ->disk('local')
                            ->directory('private/pdfs/manual') // 手動アップロード用
                            ->required()
                            ->preserveFilenames(),
                        Forms\Components\TextInput::make('storage_disk')
                            ->required()
                            ->maxLength(255)
                            ->default('local'),
                        Forms\Components\Hidden::make('size')
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('メタデータ')
                    ->schema([
                        Forms\Components\TextInput::make('year')
                            ->required()
                            ->numeric(),
                        Forms\Components\Select::make('season')
                            ->options([
                                'spring' => '春',
                                'autumn' => '秋',
                            ])
                            ->required(),
                        Forms\Components\Select::make('exam_period')
                            ->options([
                                'am2' => '午前II',
                                'pm1' => '午後I',
                                'pm2' => '午後II',
                                'pm' => '午後',
                            ])
                            ->required(),
                        Forms\Components\Select::make('doc_type')
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
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vector_store_file_id')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('index_status')
                            ->disabled()
                            ->maxLength(20)
                            ->default('pending'),
                        Forms\Components\DateTimePicker::make('indexed_at')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Textarea::make('error_message')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->searchable(),
                Tables\Columns\TextColumn::make('storage_disk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('storage_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season'),
                Tables\Columns\TextColumn::make('exam_period'),
                Tables\Columns\TextColumn::make('doc_type'),
                Tables\Columns\TextColumn::make('openai_file_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vector_store_file_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('index_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('indexed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'create' => Pages\CreatePdfFile::route('/create'),
            'edit' => Pages\EditPdfFile::route('/{record}/edit'),
        ];
    }
}
