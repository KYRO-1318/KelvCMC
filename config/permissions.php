<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission registry
    |--------------------------------------------------------------------------
    | Synced into the database by the DatabaseSeeder. Each permission maps to
    | a Gate ability / middleware check. Roles receive permissions via
    | app/Filament/Resources/RoleResource.php.
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'view_admin' => 'Access the admin panel',
        'manage_users' => 'Create / edit / delete customers',
        'manage_products' => 'Manage products & plans',
        'manage_servers' => 'Manage servers & server groups',
        'manage_orders' => 'Manage orders',
        'manage_services' => 'Manage services & provisioning',
        'manage_invoices' => 'Manage invoices',
        'manage_payments' => 'Manage payments & refunds',
        'manage_tickets' => 'Manage support tickets',
        'manage_coupons' => 'Manage coupons',
        'manage_roles' => 'Manage roles & permissions',
        'manage_settings' => 'Manage system settings',
        'view_audit_logs' => 'View audit logs',
        'api.users.read' => 'API: read users',
        'api.orders.create' => 'API: create orders',
        'api.orders.read' => 'API: read orders',
        'api.services.read' => 'API: read services',
        'api.invoices.read' => 'API: read invoices',
    ],

    'roles' => [
        'super-admin' => [
            'label' => 'Super Admin',
            'permissions' => '*',
        ],
        'admin' => [
            'label' => 'Admin',
            'permissions' => [
                'view_admin', 'manage_users', 'manage_products', 'manage_servers', 'manage_orders',
                'manage_services', 'manage_invoices', 'manage_payments', 'manage_tickets',
                'manage_coupons', 'view_audit_logs',
            ],
        ],
        'support' => [
            'label' => 'Support Agent',
            'permissions' => [
                'view_admin', 'manage_tickets', 'manage_services', 'view_audit_logs',
            ],
        ],
        'client' => [
            'label' => 'Client',
            'permissions' => [],
        ],
    ],

];
