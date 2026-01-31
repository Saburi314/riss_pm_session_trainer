<?php

namespace App\Filament\Resources\PastPaperResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PastPaperAnswerRelationManager extends RelationManager
{
    protected static string $relationship = 'pastPaperAnswers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('data.question_number')
                    ->label('問')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('data.section_id')
                    ->label('設問')
                    ->required(),
                Forms\Components\TextInput::make('data.item_id')
                    ->label('項目ID')
                    ->required(),
                Forms\Components\Textarea::make('data.answer_text')
                    ->label('模範解答')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('data.explanation')
                    ->label('解説')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('data.points')
                    ->label('配点')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('data.item_id')
            ->columns([
                Tables\Columns\TextColumn::make('data.question_number')
                    ->label('問')
                    ->sortable(),
                Tables\Columns\TextColumn::make('data.section_id')
                    ->label('設問')
                    ->sortable(),
                Tables\Columns\TextColumn::make('data.item_id')
                    ->label('項目')
                    ->sortable(),
                Tables\Columns\TextColumn::make('data.answer_text')
                    ->label('解答')
                    ->limit(50),
                Tables\Columns\TextColumn::make('data.points')
                    ->label('配点'),
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
