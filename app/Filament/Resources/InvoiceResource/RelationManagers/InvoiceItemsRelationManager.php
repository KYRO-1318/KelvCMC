<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Invoice lines';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('description')->required(),
            Forms\Components\TextInput::make('quantity')->numeric()->required()->default(1),
            Forms\Components\TextInput::make('unit_price')->numeric()->required(),
            Forms\Components\TextInput::make('total')->numeric()->required(),
            Forms\Components\Select::make('type')->options([
                'service' => 'Service',
                'setup' => 'Setup',
                'domain' => 'Domain',
                'addon' => 'Addon',
                'credit' => 'Credit',
                'other' => 'Other',
            ])->default('service'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('unit_price')->money(),
                Tables\Columns\TextColumn::make('total')->money(),
                Tables\Columns\TextColumn::make('type')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
