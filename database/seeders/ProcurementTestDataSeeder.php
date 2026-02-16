<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class ProcurementTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds for testing procurement features.
     */
    public function run(): void
    {
        // FIXED: Get tenant if system is multi-tenant
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('No tenant found. Creating data without tenant association.');
        }

        // Create test user if doesn't exist
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ];
        
        // FIXED: Add tenant_id if tenant exists
        if ($tenant) {
            $userData['tenant_id'] = $tenant->id;
        }
        
        $user = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            $userData
        );

        $this->command->info("✓ Test user: admin@test.com / password");

        // Create test vendors
        $this->createTestVendors($user->id);

        // Create test items
        $this->createTestItems($user->id);

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('Procurement Test Data Seeded!');
        $this->command->info('=================================');
        $this->command->info('Login: admin@test.com');
        $this->command->info('Password: password');
        $this->command->info('=================================');
    }

    private function createTestVendors($userId): void
    {
        $this->command->info('Creating test vendors...');

        $vendors = [
            [
                'name' => 'ABC Stationers Ltd',
                'type' => 'supplier',
                'contact_person' => 'John Smith',
                'phone' => '01711-123456',
                'email' => 'john@abcstationers.com',
                'address' => '123 Main Street, Gulshan-2, Dhaka',
                'city' => 'Dhaka',
                'postal_code' => '1212',
                'tax_id' => '1234567890',
                'rating' => 4.5,
                'status' => 'active',
                'payment_terms_days' => 30,
                'credit_limit' => 100000,
                'notes' => 'Reliable supplier for office stationery items',
            ],
            [
                'name' => 'Tech Solutions Bangladesh',
                'type' => 'supplier',
                'contact_person' => 'Sarah Ahmed',
                'phone' => '01711-234567',
                'email' => 'sarah@techsolutions.com',
                'address' => '456 Tech Plaza, Banani, Dhaka',
                'city' => 'Dhaka',
                'postal_code' => '1213',
                'tax_id' => '2345678901',
                'rating' => 5.0,
                'status' => 'active',
                'payment_terms_days' => 45,
                'credit_limit' => 500000,
                'notes' => 'Excellent for IT equipment and computers',
            ],
            [
                'name' => 'Sports & More',
                'type' => 'supplier',
                'contact_person' => 'Mike Rahman',
                'phone' => '01711-345678',
                'email' => 'mike@sportsmore.com',
                'address' => '789 Stadium Road, Mirpur, Dhaka',
                'city' => 'Dhaka',
                'postal_code' => '1216',
                'rating' => 4.0,
                'status' => 'active',
                'payment_terms_days' => 30,
                'credit_limit' => 75000,
            ],
            [
                'name' => 'Furniture World',
                'type' => 'supplier',
                'contact_person' => 'Lisa Khan',
                'phone' => '01711-456789',
                'email' => 'lisa@furnitureworld.com',
                'address' => '321 Furniture Lane, Mohammadpur, Dhaka',
                'city' => 'Dhaka',
                'postal_code' => '1207',
                'rating' => 3.5,
                'status' => 'active',
                'payment_terms_days' => 60,
                'credit_limit' => 200000,
            ],
            [
                'name' => 'Blacklisted Vendor Co',
                'type' => 'supplier',
                'contact_person' => 'Bad Supplier',
                'phone' => '01711-999999',
                'email' => 'bad@blacklisted.com',
                'status' => 'blacklisted',
                'blacklist_reason' => 'Consistently late deliveries and poor quality products',
                'blacklisted_at' => now(),
                'rating' => 1.0,
            ],
        ];

        foreach ($vendors as $vendorData) {
            $vendorData['created_by'] = $userId;
            Vendor::create($vendorData);
        }

        $this->command->info("✓ Created " . count($vendors) . " test vendors");
    }

    private function createTestItems($userId): void
    {
        $this->command->info('Creating test items...');

        // FIXED: Better error handling for missing categories
        $stationeryCategory = ItemCategory::where('name', 'Writing Materials')->first();
        $computerCategory = ItemCategory::where('name', 'Desktop Computers')->first();
        $printerCategory = ItemCategory::where('name', 'Printers & Scanners')->first();
        $furnitureCategory = ItemCategory::where('name', 'Student Furniture')->first();
        $sportsCategory = ItemCategory::where('name', 'Outdoor Sports')->first();
        
        // FIXED: Use a fallback category or create a default one
        $defaultCategoryId = $stationeryCategory?->id ?? 
                            $computerCategory?->id ?? 
                            ItemCategory::first()?->id ?? 
                            1;

        $items = [
            // Consumable items
            [
                'name' => 'Blue Ballpoint Pen',
                'description' => 'Standard blue ballpoint pen, smooth writing',
                'category_id' => $stationeryCategory?->id ?? $defaultCategoryId,
                'type' => 'consumable',
                'unit' => 'box',
                'current_price' => 50,
                'current_stock' => 150,
                'min_stock_level' => 50,
                'max_stock_level' => 200,
                'reorder_level' => 75,
                'lead_time_days' => 3,
                'is_consumable' => true,
                'is_asset' => false,
                'brand' => 'Matador',
                'status' => 'active',
            ],
            [
                'name' => 'A4 Copy Paper',
                'description' => '80 GSM white copy paper, 500 sheets per ream',
                'category_id' => $stationeryCategory?->id ?? $defaultCategoryId,
                'type' => 'consumable',
                'unit' => 'ream',
                'current_price' => 350,
                'current_stock' => 200,
                'min_stock_level' => 100,
                'max_stock_level' => 500,
                'reorder_level' => 150,
                'lead_time_days' => 5,
                'is_consumable' => true,
                'is_asset' => false,
                'brand' => 'Double A',
                'status' => 'active',
            ],
            
            // Asset items
            [
                'name' => 'HP LaserJet Pro M404dn Printer',
                'description' => 'Black & white laser printer with duplex printing',
                'category_id' => $printerCategory?->id ?? $defaultCategoryId,
                'type' => 'asset',
                'unit' => 'pcs',
                'current_price' => 35000,
                'current_stock' => 5,
                'min_stock_level' => 2,
                'max_stock_level' => 10,
                'reorder_level' => 3,
                'lead_time_days' => 7,
                'is_consumable' => false,
                'is_asset' => true,
                'asset_threshold_amount' => 5000,
                'brand' => 'HP',
                'model' => 'M404dn',
                'manufacturer' => 'HP Inc.',
                'specifications' => "Print speed: 40 ppm\nResolution: 1200 x 1200 dpi\nConnectivity: USB, Ethernet, Wi-Fi\nDuplex printing: Automatic",
                'barcode' => '123456789012',
                'status' => 'active',
            ],
            [
                'name' => 'Dell OptiPlex 7090 Desktop',
                'description' => 'Business desktop computer with Intel i7 processor',
                'category_id' => $computerCategory?->id ?? $defaultCategoryId,
                'type' => 'asset',
                'unit' => 'pcs',
                'current_price' => 65000,
                'current_stock' => 3,
                'min_stock_level' => 5,
                'max_stock_level' => 20,
                'reorder_level' => 8,
                'lead_time_days' => 10,
                'is_consumable' => false,
                'is_asset' => true,
                'asset_threshold_amount' => 10000,
                'brand' => 'Dell',
                'model' => 'OptiPlex 7090',
                'manufacturer' => 'Dell Technologies',
                'specifications' => "Processor: Intel Core i7-11700\nRAM: 16GB DDR4\nStorage: 512GB NVMe SSD\nOS: Windows 11 Pro",
                'barcode' => '234567890123',
                'status' => 'active',
            ],
            [
                'name' => 'Student Desk with Chair',
                'description' => 'Wooden student desk with attached chair',
                'category_id' => $furnitureCategory?->id ?? $defaultCategoryId,
                'type' => 'asset',
                'unit' => 'set',
                'current_price' => 2500,
                'current_stock' => 10,
                'min_stock_level' => 20,
                'max_stock_level' => 100,
                'reorder_level' => 30,
                'lead_time_days' => 14,
                'is_consumable' => false,
                'is_asset' => true,
                'asset_threshold_amount' => 1000,
                'brand' => 'SchoolFurn',
                'status' => 'active',
            ],
            
            // Both (consumable and asset depending on quantity)
            [
                'name' => 'Football (Size 5)',
                'description' => 'Professional size 5 football for outdoor play',
                'category_id' => $sportsCategory?->id ?? $defaultCategoryId,
                'type' => 'both',
                'unit' => 'pcs',
                'current_price' => 800,
                'current_stock' => 25,
                'min_stock_level' => 10,
                'max_stock_level' => 50,
                'reorder_level' => 15,
                'lead_time_days' => 7,
                'is_consumable' => true,
                'is_asset' => true,
                'asset_threshold_amount' => 500,
                'brand' => 'Adidas',
                'model' => 'Tango',
                'status' => 'active',
            ],
            
            // Low stock item
            [
                'name' => 'Whiteboard Markers',
                'description' => 'Dry erase markers, assorted colors',
                'category_id' => $stationeryCategory?->id ?? $defaultCategoryId,
                'type' => 'consumable',
                'unit' => 'box',
                'current_price' => 120,
                'current_stock' => 5,
                'min_stock_level' => 20,
                'max_stock_level' => 100,
                'reorder_level' => 30,
                'lead_time_days' => 5,
                'is_consumable' => true,
                'is_asset' => false,
                'brand' => 'Artline',
                'status' => 'active',
            ],
            
            // Out of stock item
            [
                'name' => 'Scientific Calculator',
                'description' => 'Scientific calculator for mathematics',
                'category_id' => $stationeryCategory?->id ?? $defaultCategoryId,
                'type' => 'consumable',
                'unit' => 'pcs',
                'current_price' => 450,
                'current_stock' => 0,
                'min_stock_level' => 10,
                'max_stock_level' => 50,
                'reorder_level' => 15,
                'lead_time_days' => 7,
                'is_consumable' => true,
                'is_asset' => false,
                'brand' => 'Casio',
                'model' => 'FX-991EX',
                'status' => 'active',
            ],
            
            // Inactive item
            [
                'name' => 'Old Model Projector',
                'description' => 'Discontinued projector model',
                'category_id' => $computerCategory?->id ?? $defaultCategoryId,
                'type' => 'asset',
                'unit' => 'pcs',
                'current_price' => 45000,
                'current_stock' => 2,
                'min_stock_level' => 0,
                'max_stock_level' => 5,
                'reorder_level' => 0,
                'lead_time_days' => 0,
                'is_consumable' => false,
                'is_asset' => true,
                'status' => 'discontinued',
            ],
        ];

        foreach ($items as $itemData) {
            $itemData['created_by'] = $userId;
            Item::create($itemData);
        }

        $this->command->info("✓ Created " . count($items) . " test items");
        $this->command->info("  - Consumable items: 4");
        $this->command->info("  - Asset items: 4");
        $this->command->info("  - Both type: 1");
        $this->command->info("  - Low stock: 1");
        $this->command->info("  - Out of stock: 1");
        $this->command->info("  - Discontinued: 1");
    }
}