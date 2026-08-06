<?php

namespace App\Filament\Resources;

use App\Enums\ProductType;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ServerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', str()->slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')->required()->options(ProductType::labels()),
                Forms\Components\Select::make('module')->required()->options([
                    'plesk' => 'Plesk',
                    'pterodactyl' => 'Pterodactyl',
                    'proxmox' => 'Proxmox',
                    'manual' => 'Manual',
                ])->helperText('Integration used to provision this product.'),
                Forms\Components\Select::make('server_group_id')->label('Server group')->relationship('serverGroup', 'name')->nullable(),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Toggle::make('is_featured'),
                Forms\Components\Toggle::make('is_recurring')->default(true),
                Forms\Components\TextInput::make('stock')->numeric()->nullable()->helperText('Leave empty for unlimited'),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Pricing (without tax)')->schema([
                Forms\Components\TextInput::make('price_monthly')->numeric()->required(),
                Forms\Components\TextInput::make('price_quarterly')->numeric()->nullable(),
                Forms\Components\TextInput::make('price_semi_annually')->numeric()->nullable(),
                Forms\Components\TextInput::make('price_annually')->numeric()->nullable(),
                Forms\Components\TextInput::make('setup_fee')->numeric()->default(0),
            ])->columns(5),

            Forms\Components\Section::make('Features & metadata')->schema([
                Forms\Components\KeyValue::make('features')->keyLabel('Feature')->valueLabel('Value')->addActionLabel('Add feature'),
                Forms\Components\KeyValue::make('metadata')->keyLabel('Key')->valueLabel('Value'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('module')->badge()->color('info'),
                Tables\Columns\TextColumn::make('price_monthly')->money()->label('Monthly'),
                Tables\Columns\TextColumn::make('plans_count')->label('Plans')->counts('plans'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(ProductType::labels()),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
        ];
    }
}
