<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')->required()->rows(4),
            Forms\Components\Toggle::make('is_internal')->label('Internal note (not visible to customer)')->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('author_name')->label('From'),
                Tables\Columns\TextColumn::make('body')->limit(60)->wrap(),
                Tables\Columns\IconColumn::make('is_admin')->label('Staff')->boolean(),
                Tables\Columns\IconColumn::make('is_internal')->label('Internal')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Reply')
                    ->using(function (array $data): Model {
                        $record = $this->getOwnerRecord();

                        $message = $record->messages()->create([
                            'user_id' => auth()->id(),
                            'body' => $data['body'],
                            'is_internal' => $data['is_internal'] ?? false,
                            'is_admin' => true,
                        ]);

                        if (empty($data['is_internal'])) {
                            app(NotificationService::class)->ticketReply($record, $record->user);
                        }

                        $record->update([
                            'status' => 'answered',
                            'last_reply_at' => now(),
                        ]);

                        return $message;
                    }),
            ])
            ->defaultSort('created_at');
    }
}
