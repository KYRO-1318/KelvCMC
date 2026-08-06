<?php

namespace App\Filament\Resources;

use App\Enums\ServiceStatus;
use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Services\ProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'Hosting';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('user_id')->label('Customer')->relationship('user', 'name')->searchable()->required(),
                Forms\Components\Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable(),
                Forms\Components\Select::make('plan_id')->label('Plan')->relationship('plan', 'name')->searchable(),
                Forms\Components\Select::make('server_id')->label('Server')->relationship('server', 'name')->searchable(),
                Forms\Components\TextInput::make('domain'),
                Forms\Components\Select::make('status')->options(collect(ServiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Forms\Components\Select::make('billing_cycle')->default('monthly')->options([
                    'monthly' => 'Monthly',
                    'quarterly' => 'Quarterly',
                    'semi_annually' => 'Semi-annually',
                    'annually' => 'Annually',
                    'onetime' => 'One time',
                ]),
                Forms\Components\TextInput::make('price')->numeric()->required(),
                Forms\Components\TextInput::make('username'),
                Forms\Components\TextInput::make('remote_id')->label('Provider ID'),
                Forms\Components\DateTimePicker::make('expires_at'),
                Forms\Components\KeyValue::make('provisioning_data')->keyLabel('Key')->valueLabel('Value'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product'),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan'),
                Tables\Columns\TextColumn::make('domain')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('price')->money()->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(ServiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('product')->relationship('product', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('provision')
                    ->label('Provision')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Service $record) => $record->status === 'pending')
                    ->action(fn (Service $record, ProvisioningService $service) => $service->provision($record)),
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Service $record) => in_array($record->status, ['pending', 'active']))
                    ->action(fn (Service $record, ProvisioningService $service) => $service->suspend($record)),
                Tables\Actions\Action::make('unsuspend')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Service $record) => $record->status === 'suspended')
                    ->action(fn (Service $record, ProvisioningService $service) => $service->unsuspend($record)),
                Tables\Actions\Action::make('terminate')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Service $record) => ! in_array($record->status, ['terminated']))
                    ->action(function (Service $record, ProvisioningService $service) {
                        $service->terminate($record);
                        Notification::make()->title('Service terminated')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServices::route('/'),
        ];
    }
}
