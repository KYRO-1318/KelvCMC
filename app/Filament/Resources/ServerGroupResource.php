<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerGroupResource\Pages;
use App\Models\ServerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerGroupResource extends Resource
{
    protected static ?string $model = ServerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'Hosting';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('integration')->required()->options([
                'plesk' => 'Plesk',
                'pterodactyl' => 'Pterodactyl',
                'proxmox' => 'Proxmox',
                'manual' => 'Manual',
            ]),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('integration')->badge()->color('info'),
                Tables\Columns\TextColumn::make('servers_count')->counts('servers')->label('Servers'),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('Products'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServerGroups::route('/'),
        ];
    }
}
