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
                            ->itemLabel(fn(array $state): ?string => '問' . ($state['question_number'] ?? '?') . ': ' . ($state['title'] ?? ''))
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
                                    ->itemLabel(fn(array $state): ?string => '設問' . ($state['section_id'] ?? '?'))
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('section_id')
                                                    ->label('設問番号')
                                                    ->placeholder('例: 1')
                                                    ->required(),
                                                Forms\Components\Textarea::make('section_text')
                                                    ->label('設問文 / 説明')
                                                    ->placeholder('例: 下線①について、...'),
                                            ]),

                                        Forms\Components\Repeater::make('items')
                                            ->label('解答項目')
                                            ->itemLabel(fn(array $state): ?string => '項目' . ($state['item_id'] ?? '?') . ' (' . ($state['answer_type'] ?? 'text') . ')')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('item_id')
                                                            ->label('番号/記号')
                                                            ->placeholder('例: a, (1), 1')
                                                            ->required(),
                                                        Forms\Components\Select::make('answer_type')
                                                            ->label('タイプ')
                                                            ->options([
                                                                'text' => 'テキスト記述',
                                                                'radio' => '選択肢 (Radio)',
                                                                'checkbox' => '複数選択 (Checkbox)',
                                                                'select' => 'リスト (Select)',
                                                            ])
                                                            ->default('text')
                                                            ->reactive(),
                                                        Forms\Components\TextInput::make('char_limit')
                                                            ->label('字数制限')
                                                            ->numeric()
                                                            ->placeholder('例: 30'),
                                                    ]),
                                                
                                                Forms\Components\TextInput::make('item_text')
                                                    ->label('項目ラベル (空でもOK)')
                                                    ->placeholder('例: (1) 下線①について...'),

                                                Forms\Components\TagsInput::make('choices')
                                                    ->label('選択肢')
                                                    ->placeholder('ア:..., イ:...')
                                                    ->helperText('選択式の場合のみ入力')
                                                    ->visible(fn(Forms\Get $get) => in_array($get('answer_type'), ['radio', 'checkbox', 'select']))
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(1)
                                            ->collapsible()
                                            ->collapsed(fn($state) => !empty($state)) // データがあれば初期状態は閉じる
                                    ])
                                    ->collapsible()
                                    ->collapsed(fn($state) => !empty($state))
                            ])->collapsible()
                    ])
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('確認済')
                    ->boolean(),
                Tables\Columns\TextColumn::make('summary')
                    ->label('内容概要')
                    ->state(function ($record): string {
                        $questions = $record->data['questions'] ?? [];
                        $count = count($questions);
                        if ($count === 0)
                            return 'データなし';

                        $firstTitle = mb_strimwidth($questions[0]['title'] ?? '', 0, 40, '...');
                        return "全{$count}問: {$firstTitle}";
                    })
                    ->description(fn($record) => $record->updated_at->diffForHumans()),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日時')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
