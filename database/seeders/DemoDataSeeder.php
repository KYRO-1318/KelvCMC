<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // Server infrastructure
        // ------------------------------------------------------------------
        $pleskGroup = ServerGroup::firstOrCreate(['name' => 'Plesk Cluster EU'], ['integration' => 'plesk']);
        $pteroGroup = ServerGroup::firstOrCreate(['name' => 'Game Nodes'], ['integration' => 'pterodactyl']);
        $pveGroup = ServerGroup::firstOrCreate(['name' => 'VPS Nodes'], ['integration' => 'proxmox']);

        $pleskServer = Server::create([
            'server_group_id' => $pleskGroup->id,
            'name' => 'plesk-eu-01',
            'hostname' => 'plesk-eu-01.example.com',
            'ip_address' => '203.0.113.10',
            'integration' => 'plesk',
            'remote_id' => '1',
            'status' => 'online',
            'location' => 'Frankfurt',
            'metadata' => ['nodes' => 40],
        ]);

        $pteroServers = [
            Server::create([
                'server_group_id' => $pteroGroup->id,
                'name' => 'game-01',
                'hostname' => 'game-01.example.com',
                'ip_address' => '203.0.113.20',
                'integration' => 'pterodactyl',
                'remote_id' => '1',
                'status' => 'online',
                'location' => 'Paris',
            ]),
            Server::create([
                'server_group_id' => $pteroGroup->id,
                'name' => 'game-02',
                'hostname' => 'game-02.example.com',
                'ip_address' => '203.0.113.21',
                'integration' => 'pterodactyl',
                'remote_id' => '2',
                'status' => 'online',
                'location' => 'Amsterdam',
            ]),
        ];

        Server::create([
            'server_group_id' => $pveGroup->id,
            'name' => 'vps-node-01',
            'hostname' => 'vps-node-01.example.com',
            'ip_address' => '203.0.113.30',
            'integration' => 'proxmox',
            'remote_id' => 'node1',
            'status' => 'online',
            'location' => 'Strasbourg',
        ]);

        // ------------------------------------------------------------------
        // Products
        // ------------------------------------------------------------------
        $webhosting = Product::create([
            'name' => 'Shared Web Hosting',
            'slug' => 'shared-web-hosting',
            'description' => 'Fast and reliable shared hosting with Plesk control panel, SSL and daily backups.',
            'type' => 'webhosting',
            'module' => 'plesk',
            'price_monthly' => 4.99,
            'price_quarterly' => 13.49,
            'price_semi_annually' => 25.49,
            'price_annually' => 47.99,
            'setup_fee' => 0,
            'features' => ['SSL certificate' => 'Free Let\'s Encrypt', 'Backups' => 'Daily', 'Support' => '24/7'],
            'is_active' => true,
            'is_featured' => true,
            'server_group_id' => $pleskGroup->id,
            'sort_order' => 1,
        ]);

        Plan::create([
            'product_id' => $webhosting->id,
            'name' => 'Starter',
            'price_monthly' => 2.99,
            'price_quarterly' => 8.09,
            'price_semi_annually' => 15.29,
            'price_annually' => 28.99,
            'disk_mb' => 10240,
            'bandwidth_gb' => 100,
            'databases' => 2,
            'email_accounts' => 5,
            'domains' => 1,
            'sort_order' => 1,
        ]);

        Plan::create([
            'product_id' => $webhosting->id,
            'name' => 'Business',
            'price_monthly' => 6.99,
            'price_quarterly' => 18.89,
            'price_semi_annually' => 35.69,
            'price_annually' => 66.99,
            'disk_mb' => 51200,
            'bandwidth_gb' => 500,
            'databases' => 10,
            'email_accounts' => 25,
            'domains' => 10,
            'sort_order' => 2,
        ]);

        Plan::create([
            'product_id' => $webhosting->id,
            'name' => 'Enterprise',
            'price_monthly' => 14.99,
            'price_quarterly' => 40.49,
            'price_semi_annually' => 76.49,
            'price_annually' => 143.99,
            'disk_mb' => 204800,
            'bandwidth_gb' => 2000,
            'databases' => 50,
            'email_accounts' => 100,
            'domains' => 999,
            'sort_order' => 3,
        ]);

        $vps = Product::create([
            'name' => 'Cloud VPS',
            'slug' => 'cloud-vps',
            'description' => 'KVM virtual servers on NVMe storage, full root access, deployed in minutes.',
            'type' => 'vps',
            'module' => 'proxmox',
            'price_monthly' => 7.99,
            'price_quarterly' => 21.59,
            'price_annually' => 76.79,
            'features' => ['CPU' => '2+ vCores', 'Storage' => 'NVMe', 'Network' => '1 Gbps'],
            'is_active' => true,
            'is_featured' => true,
            'server_group_id' => $pveGroup->id,
            'sort_order' => 2,
        ]);

        Plan::create([
            'product_id' => $vps->id,
            'name' => 'VPS S',
            'price_monthly' => 7.99,
            'price_quarterly' => 21.59,
            'price_annually' => 76.79,
            'cpu_cores' => 2,
            'ram_mb' => 2048,
            'disk_mb' => 40960,
            'swap_mb' => 1024,
            'sort_order' => 1,
        ]);

        Plan::create([
            'product_id' => $vps->id,
            'name' => 'VPS M',
            'price_monthly' => 14.99,
            'price_quarterly' => 40.49,
            'price_annually' => 143.99,
            'cpu_cores' => 4,
            'ram_mb' => 4096,
            'disk_mb' => 81920,
            'swap_mb' => 2048,
            'sort_order' => 2,
        ]);

        $minecraft = Product::create([
            'name' => 'Minecraft Server',
            'slug' => 'minecraft-server',
            'description' => 'Minecraft Java servers with one-click modpack install, DDoS protection.',
            'type' => 'minecraft',
            'module' => 'pterodactyl',
            'price_monthly' => 5.49,
            'price_quarterly' => 14.99,
            'price_annually' => 53.99,
            'is_active' => true,
            'is_featured' => true,
            'server_group_id' => $pteroGroup->id,
            'sort_order' => 3,
        ]);

        Plan::create([
            'product_id' => $minecraft->id,
            'name' => 'MC 2GB',
            'price_monthly' => 5.49,
            'price_quarterly' => 14.99,
            'price_annually' => 53.99,
            'cpu_cores' => 2,
            'ram_mb' => 2048,
            'disk_mb' => 15360,
            'swap_mb' => 1024,
            'sort_order' => 1,
        ]);

        Plan::create([
            'product_id' => $minecraft->id,
            'name' => 'MC 8GB',
            'price_monthly' => 19.99,
            'price_quarterly' => 53.99,
            'price_annually' => 191.99,
            'cpu_cores' => 4,
            'ram_mb' => 8192,
            'disk_mb' => 30720,
            'swap_mb' => 2048,
            'sort_order' => 2,
        ]);

        Product::create([
            'name' => 'FiveM Server',
            'slug' => 'fivem-server',
            'description' => 'High-performance FiveM / Cfx.re servers, 24/7 uptime.',
            'type' => 'fivem',
            'module' => 'pterodactyl',
            'price_monthly' => 8.99,
            'price_annually' => 86.39,
            'is_active' => true,
            'server_group_id' => $pteroGroup->id,
            'sort_order' => 4,
        ]);

        Product::create([
            'name' => '.COM Domain',
            'slug' => 'com-domain',
            'description' => 'Register or renew your .com domain with free DNS management.',
            'type' => 'domain',
            'module' => 'manual',
            'price_monthly' => 1.25,
            'price_annually' => 13.99,
            'is_recurring' => true,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        Product::create([
            'name' => 'Plesk License',
            'slug' => 'plesk-license',
            'description' => 'Plesk Web Admin SE licenses for your servers.',
            'type' => 'license',
            'module' => 'manual',
            'price_monthly' => 5.00,
            'price_annually' => 48.00,
            'is_active' => true,
            'sort_order' => 6,
        ]);

        // ------------------------------------------------------------------
        // Coupons
        // ------------------------------------------------------------------
        Coupon::create([
            'code' => 'WELCOME20',
            'type' => 'percent',
            'value' => 20,
            'max_uses' => 500,
            'is_active' => true,
            'expires_at' => now()->addYear(),
        ]);

        Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'min_amount' => 50,
            'max_discount' => 25,
            'max_uses' => 1000,
            'is_active' => true,
            'expires_at' => now()->addMonths(6),
        ]);

        // ------------------------------------------------------------------
        // Demo customers & data
        // ------------------------------------------------------------------
        $customer = User::firstOrCreate(
            ['email' => 'client@kelvcmc.local'],
            [
                'name' => 'Demo Client',
                'company' => 'Demo Corp',
                'password' => Hash::make('password'),
                'country' => 'FR',
                'city' => 'Paris',
                'is_active' => true,
                'credit_balance' => 25.00,
            ]
        );
        $customer->assignRole('client');

        $order = Order::create([
            'number' => Order::generateNumber(),
            'user_id' => $customer->id,
            'status' => 'paid',
            'subtotal' => 4.99,
            'discount' => 1.00,
            'tax' => 0.80,
            'total' => 4.79,
            'placed_at' => now()->subMonths(2),
            'paid_at' => now()->subMonths(2),
        ]);

        $order->items()->create([
            'product_id' => $webhosting->id,
            'plan_id' => $webhosting->plans()->first()->id,
            'description' => 'Shared Web Hosting — Business',
            'quantity' => 1,
            'unit_price' => 6.99,
            'total' => 6.99,
            'billing_cycle' => 'monthly',
        ]);

        $service = Service::create([
            'user_id' => $customer->id,
            'order_item_id' => $order->items()->first()->id,
            'product_id' => $webhosting->id,
            'plan_id' => $webhosting->plans()->first()->id,
            'server_id' => $pleskServer->id,
            'server_group_id' => $pleskGroup->id,
            'name' => 'Shared Web Hosting — Business',
            'domain' => 'demo-client.example.com',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 6.99,
            'username' => 'kc_demo1',
            'remote_id' => '42',
            'provisioning_data' => [
                'plesk_webspace_id' => 42,
                'plesk_client_id' => 7,
                'databases' => ['name' => 'demo_client', 'user' => 'kc_demodb'],
                'domain' => 'demo-client.example.com',
            ],
            'activated_at' => now()->subMonths(2),
            'expires_at' => now()->addDays(20),
        ]);

        $invoice = Invoice::create([
            'number' => Invoice::generateNumber(),
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'service_id' => $service->id,
            'status' => 'paid',
            'subtotal' => 6.99,
            'discount' => 0,
            'tax_rate' => 20,
            'tax_amount' => 1.40,
            'total' => 8.39,
            'currency' => 'EUR',
            'due_at' => now()->subMonths(1),
            'paid_at' => now()->subMonths(1),
        ]);

        $invoice->items()->create([
            'description' => 'Shared Web Hosting — Business (Monthly)',
            'quantity' => 1,
            'unit_price' => 6.99,
            'total' => 6.99,
            'tax_rate' => 20,
            'type' => 'service',
        ]);

        Payment::create([
            'reference' => Payment::generateReference(),
            'invoice_id' => $invoice->id,
            'user_id' => $customer->id,
            'gateway' => 'stripe',
            'transaction_id' => 'pi_demo_123456',
            'amount' => 8.39,
            'currency' => 'EUR',
            'status' => 'paid',
            'paid_at' => now()->subMonths(1),
        ]);

        // Open invoice (payable)
        $openInvoice = Invoice::create([
            'number' => Invoice::generateNumber(),
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'open',
            'subtotal' => 6.99,
            'discount' => 0,
            'tax_rate' => 20,
            'tax_amount' => 1.40,
            'total' => 8.39,
            'currency' => 'EUR',
            'due_at' => now()->addDays(5),
        ]);

        $openInvoice->items()->create([
            'description' => 'Shared Web Hosting — Business (Renewal)',
            'quantity' => 1,
            'unit_price' => 6.99,
            'total' => 6.99,
            'tax_rate' => 20,
            'type' => 'service',
        ]);

        // Demo ticket
        $technical = \App\Models\TicketCategory::where('name', 'Technical support')->first();

        $ticket = Ticket::create([
            'number' => Ticket::generateNumber(),
            'user_id' => $customer->id,
            'ticket_category_id' => $technical?->id,
            'subject' => 'How do I point my domain to the server?',
            'priority' => 'medium',
            'status' => 'answered',
            'last_reply_at' => now()->subDay(),
        ]);

        $ticket->messages()->create([
            'user_id' => $customer->id,
            'body' => 'Hi! I just ordered shared hosting. How do I point my domain to it?',
            'is_admin' => false,
        ]);

        $ticket->messages()->create([
            'user_id' => $customer->id,
            'body' => 'Hello! Update the A record of your domain to 203.0.113.10 and it will be live within a few minutes. Let us know if you need a hand.',
            'is_admin' => true,
        ]);

        // Second demo client
        $client2 = User::firstOrCreate(
            ['email' => 'second@kelvcmc.local'],
            [
                'name' => 'Second Client',
                'password' => Hash::make('password'),
                'country' => 'BE',
                'is_active' => true,
            ]
        );
        $client2->assignRole('client');
    }
}
