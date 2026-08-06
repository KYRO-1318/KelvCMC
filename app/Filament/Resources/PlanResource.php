<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('product_id')->label('Product')->relationship('product', 'name')->required()->searchable(),
                Forms\Components\Textarea::make('description')->rows(2),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Pricing')->schema([
                Forms\Components\TextInput::make('price_monthly')->numeric()->required(),
                Forms\Components\TextInput::make('price_quarterly')->numeric()->nullable(),
                Forms\Components\TextInput::make('price_semi_annually')->numeric()->nullable(),
                Forms\Components\TextInput::make('price_annually')->numeric()->nullable(),
                Forms\Components\TextInput::make('setup_fee')->numeric()->default(0),
            ])->columns(5),

            Forms\Components\Section::make('Resource limits (used by provisioning drivers)')->schema([
                Forms\Components\TextInput::make('disk_mb')->numeric()->suffix('MB'),
                Forms\Components\TextInput::make('bandwidth_gb')->numeric()->suffix('GB'),
                Forms\Components\TextInput::make('cpu_cores')->numeric()->suffix('cores'),
                Forms\Components\TextInput::make('ram_mb')->numeric()->suffix('MB'),
                Forms\Components\TextInput::make('swap_mb')->numeric()->suffix('MB'),
                Forms\Components\TextInput::make('databases')->numeric(),
                Forms\Components\TextInput::make('email_accounts')->numeric(),
                Forms\Components\TextInput::make('domains')->numeric(),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('product.name')->sortable(),
                Tables\Columns\TextColumn::make('price_monthly')->money()->label('Monthly'),
                Tables\Columns\TextColumn::make('ram_mb')->label('RAM')->formatStateUsing(fn ($state) => $state ? $state.' MB' : '-'),
                Tables\Columns\TextColumn::make('disk_mb')->label('Disk')->formatStateUsing(fn ($state) => $state ? $state.' MB' : '-'),
                Tables\Columns\TextColumn::make('cpu_cores')->label('CPU'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')->relationship('product', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePlans::route('/'),
        ];
    }
}
