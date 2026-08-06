<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->uppercase(),
                Forms\Components\Select::make('type')->required()->options([
                    'percent' => 'Percentage',
                    'fixed' => 'Fixed amount',
                ])->default('percent'),
                Forms\Components\TextInput::make('value')->numeric()->required(),
                Forms\Components\TextInput::make('min_amount')->numeric()->nullable(),
                Forms\Components\TextInput::make('max_discount')->numeric()->nullable()->helperText('Only for percentage coupons'),
                Forms\Components\TextInput::make('max_uses')->numeric()->nullable(),
                Forms\Components\Toggle::make('first_order_only'),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\DateTimePicker::make('starts_at'),
                Forms\Components\DateTimePicker::make('expires_at'),
            ])->columns(2),

            Forms\Components\Section::make('Restrictions')->schema([
                Forms\Components\TagsInput::make('product_ids')->label('Product IDs (empty = all)'),
                Forms\Components\TagsInput::make('cycles')->label('Allowed billing cycles')->helperText('monthly, quarterly, annually...'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable()->badge()->color('primary'),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('value')->suffix(fn (Coupon $record) => $record->type === 'percent' ? '%' : ''),
                Tables\Columns\TextColumn::make('used')->label('Used')->suffix(fn (Coupon $record) => $record->max_uses ? ' / '.$record->max_uses : ''),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable()->placeholder('Never'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCoupons::route('/'),
        ];
    }
}
