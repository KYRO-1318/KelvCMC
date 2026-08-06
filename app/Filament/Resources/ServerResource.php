<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Hosting';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('server_group_id')->label('Server group')->relationship('serverGroup', 'name'),
                Forms\Components\Select::make('integration')->required()->options([
                    'plesk' => 'Plesk',
                    'pterodactyl' => 'Pterodactyl',
                    'proxmox' => 'Proxmox',
                    'manual' => 'Manual',
                ]),
                Forms\Components\TextInput::make('hostname')->required()->maxLength(255)->helperText('IP or hostname used by the API.'),
                Forms\Components\TextInput::make('ip_address')->maxLength(45),
                Forms\Components\TextInput::make('remote_id')->label('Provider ID')->helperText('Optional: node / server id on the provider side.'),
                Forms\Components\TextInput::make('location')->maxLength(255),
                Forms\Components\Select::make('status')->default('online')->options([
                    'online' => 'Online',
                    'offline' => 'Offline',
                    'maintenance' => 'Maintenance',
                ]),
                Forms\Components\KeyValue::make('credentials')->label('Credentials (encrypted)')->keyLabel('Key')->valueLabel('Secret'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('hostname')->searchable(),
                Tables\Columns\TextColumn::make('integration')->badge()->color('info'),
                Tables\Columns\TextColumn::make('serverGroup.name')->label('Group'),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('services_count')->counts('services')->label('Services'),
                Tables\Columns\TextColumn::make('last_checked_at')->dateTime()->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('integration'),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServers::route('/'),
        ];
    }
}
