<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'System';

    protected static bool $isGloballySearchable = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Actor')->searchable(),
                Tables\Columns\TextColumn::make('action')->badge()->color('info')->searchable(),
                Tables\Columns\TextColumn::make('model_type')->label('Subject')->formatStateUsing(fn ($state) => class_basename((string) $state))->searchable(),
                Tables\Columns\TextColumn::make('model_id')->label('ID'),
                Tables\Columns\TextColumn::make('ip')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')->options(fn () => AuditLog::distinct()->pluck('action', 'action')->all()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
