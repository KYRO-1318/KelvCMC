<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\TicketCategory;
use App\Models\TicketDepartment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Default settings
        Setting::set('brand.name', config('kelvcmc.brand.name', 'KelvCMC'));
        Setting::set('brand.tagline', config('kelvcmc.brand.tagline'));
        Setting::set('billing.currency', config('kelvcmc.billing.currency', 'EUR'));
        Setting::set('billing.tax_rate', config('kelvcmc.billing.default_tax_rate', 20), 'billing');
        Setting::set('billing.days_before_renewal', config('kelvcmc.billing.days_before_renewal', 7), 'billing');
        Setting::set('appearance.active_theme', config('themes.default', 'kelv'), 'appearance');
        Setting::set('security.force_2fa_for_admins', config('kelvcmc.security.force_2fa_for_admins'), 'security');

        // Support taxonomy
        foreach (['Billing', 'Technical support', 'Domains & DNS', 'Sales'] as $i => $category) {
            TicketCategory::firstOrCreate(['name' => $category], ['color' => ['violet', 'sky', 'emerald', 'amber'][$i], 'sort_order' => $i]);
        }

        foreach (['Billing', 'Support', 'Sales'] as $i => $department) {
            TicketDepartment::firstOrCreate(['name' => $department]);
        }
    }
}
