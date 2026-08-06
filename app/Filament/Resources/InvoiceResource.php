<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers\InvoiceItemsRelationManager;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('number')->required()->maxLength(50),
                Forms\Components\Select::make('user_id')->label('Customer')->relationship('user', 'name')->searchable()->required(),
                Forms\Components\Select::make('status')->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))->required(),
                Forms\Components\TextInput::make('subtotal')->numeric()->required(),
                Forms\Components\TextInput::make('discount')->numeric()->default(0),
                Forms\Components\TextInput::make('tax_rate')->numeric()->default(0),
                Forms\Components\TextInput::make('tax_amount')->numeric()->default(0),
                Forms\Components\TextInput::make('total')->numeric()->required(),
                Forms\Components\DateTimePicker::make('due_at'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('total')->money()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('due_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => in_array($record->status, ['open', 'overdue']))
                    ->action(fn (Invoice $record, BillingService $billing) => $billing->markInvoiceAsPaid($record)),
                Tables\Actions\Action::make('remind')
                    ->label('Send reminder')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn (Invoice $record) => in_array($record->status, ['open', 'overdue']))
                    ->action(function (Invoice $record, NotificationService $notifications) {
                        $notifications->invoiceReminder($record);
                        $record->update(['reminded_at' => now()]);
                        Notification::make()->title('Reminder sent')->success()->send();
                    }),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            InvoiceItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
