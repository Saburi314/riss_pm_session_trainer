<?php

namespace App\Filament\Resources\PastPaperResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PastPaperQuestionRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('filename') // 紐付け用
                    ->label('紐付けファイル名')
                    ->disabled()
                    ->hidden(),

                Forms\Components\Section::make('設問データ構造')
                    ->schema([
                        Forms\Components\Repeater::make('data.questions')
                            ->label('大問')
                            ->schema([
                                Forms\Components\TextInput::make('question_number')
                                    ->label('問番号')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('title')
                                    ->label('タイトル')
                                    ->placeholder('例: 問1 ネットワークセキュリティ'),

                                Forms\Components\Repeater::make('sections')
                                    ->label('設問')
                                    ->schema([
                                        Forms\Components\TextInput::make('section_id')
                                            ->label('設問番号 (1, 2...)')
                                            ->required(),
                                        Forms\Components\Textarea::make('section_text')
                                            ->label('設問文 / 説明'),

                                        Forms\Components\Repeater::make('items')
                                            ->label('解答項目')
                                            ->schema([
                                                Forms\Components\TextInput::make('item_id')
                                                    ->label('項目ID (a, b, 1...)')
                                                    ->required(),
                                                Forms\Components\TextInput::make('item_text')
                                                    ->label('ラベル'),
                                                Forms\Components\TextInput::make('char_limit')
                                                    ->label('文字数制限')
                                                    ->numeric(),
                                                Forms\Components\Select::make('answer_type')
                                                    ->label('入力タイプ')
                                                    ->options([
                                                        'text' => 'テキスト',
                                                        'select' => '選択肢',
                                                    ])
                                                    ->default('text'),
                                            ])->columns(4)
                                    ])->collapsible()
                            ])->collapsible()
                    ])
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('data')
            ->columns([
                Tables\Columns\TextColumn::make('data'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
