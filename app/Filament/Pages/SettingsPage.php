<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AuditLogger;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SettingsPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'brand.name' => Setting::get('brand.name', config('kelvcmc.brand.name')),
            'brand.tagline' => Setting::get('brand.tagline', config('kelvcmc.brand.tagline')),
            'billing.currency' => Setting::get('billing.currency', config('kelvcmc.billing.currency')),
            'billing.tax_rate' => Setting::get('billing.tax_rate', config('kelvcmc.billing.default_tax_rate')),
            'billing.days_before_renewal' => Setting::get('billing.days_before_renewal', config('kelvcmc.billing.days_before_renewal')),
            'appearance.active_theme' => Setting::get('appearance.active_theme', config('themes.default')),
            'security.force_2fa_for_admins' => Setting::get('security.force_2fa_for_admins', config('kelvcmc.security.force_2fa_for_admins')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Branding')
                    ->schema([
                        TextInput::make('brand.name')->label('Brand name')->required(),
                        TextInput::make('brand.tagline')->label('Tagline'),
                    ])->columns(2),

                Section::make('Billing')
                    ->schema([
                        TextInput::make('billing.currency')->label('Currency code')->maxLength(3)->required(),
                        TextInput::make('billing.tax_rate')->label('Default VAT / tax rate (%)')->numeric()->required(),
                        TextInput::make('billing.days_before_renewal')->label('Generate renewal invoice X days before expiry')->numeric(),
                    ])->columns(3),

                Section::make('Appearance')
                    ->schema([
                        Select::make('appearance.active_theme')
                            ->label('Client portal theme')
                            ->options(collect(config('themes.themes'))->mapWithKeys(fn ($t, $k) => [$k => $t['name']])->all()),
                    ]),

                Section::make('Security')
                    ->schema([
                        Toggle::make('security.force_2fa_for_admins')->label('Force two-factor authentication for administrators'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $group => $values) {
            foreach ($values as $key => $value) {
                if ($value !== null) {
                    Setting::set("{$group}.{$key}", $value, $group);
                }
            }
        }

        AuditLogger::record('settings.updated', null, ['changes' => $state]);

        Notification::make()->title('Settings saved')->success()->send();
    }
}
