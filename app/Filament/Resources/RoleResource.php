<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\CheckboxList::make('permissions')
                ->label('Permissions')
                ->relationship('permissions', 'label')
                ->columns(3)
                ->options(Permission::orderBy('label')->pluck('label', 'id')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('name')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('permissions_count')->counts('permissions')->label('Permissions'),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(fn (Role $record) => in_array($record->name, ['super-admin', 'admin', 'client'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
