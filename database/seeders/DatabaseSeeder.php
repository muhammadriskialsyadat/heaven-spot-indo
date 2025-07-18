<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@heaven-spot-indo.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create categories
        $categories = [
            ['name' => 'Cat Tembok', 'description' => 'Cat untuk dinding interior dan eksterior'],
            ['name' => 'Cat Kayu', 'description' => 'Cat khusus untuk furniture kayu'],
            ['name' => 'Cat Besi', 'description' => 'Cat anti karat untuk logam'],
            ['name' => 'Cat Primer', 'description' => 'Cat dasar sebelum finishing'],
            ['name' => 'Cat Semprot', 'description' => 'Cat dalam kemasan spray'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create suppliers
        $suppliers = [
            [
                'name' => 'PT Mowilex Indonesia',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-5555-0001',
                'email' => 'budi@mowilex.com',
                'address' => 'Jl. Industri No. 123, Jakarta'
            ],
            [
                'name' => 'CV Avitex Paint',
                'contact_person' => 'Siti Rahayu',
                'phone' => '021-5555-0002',
                'email' => 'siti@avitex.com',
                'address' => 'Jl. Raya Bogor No. 456, Depok'
            ],
            [
                'name' => 'Toko Cat Jaya',
                'contact_person' => 'Ahmad Wijaya',
                'phone' => '021-5555-0003',
                'email' => 'ahmad@catjaya.com',
                'address' => 'Jl. Kemang Raya No. 789, Jakarta Selatan'
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        // Create customers
        $customers = [
            [
                'name' => 'Toko Bangunan Sumber Rejeki',
                'phone' => '021-7777-0001',
                'email' => 'sumberrejeki@gmail.com',
                'address' => 'Jl. Pasar Minggu No. 111, Jakarta Selatan'
            ],
            [
                'name' => 'CV Mitra Konstruksi',
                'phone' => '021-7777-0002',
                'email' => 'mitra@konstruksi.com',
                'address' => 'Jl. Sudirman No. 222, Jakarta Pusat'
            ],
            [
                'name' => 'Kontraktor Bangunan Jaya',
                'phone' => '021-7777-0003',
                'email' => 'jaya@kontraktor.com',
                'address' => 'Jl. Thamrin No. 333, Jakarta Pusat'
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Create sample products
        $products = [
            [
                'name' => 'Mowilex Emulsion Paint',
                'code' => 'MWX-001',
                'category_id' => 1,
                'brand' => 'Mowilex',
                'color' => 'Putih',
                'size' => '2.5L',
                'unit' => 'Kaleng',
                'minimum_stock' => 10,
                'current_stock' => 25,
                'purchase_price' => 85000,
                'selling_price' => 95000,
                'description' => 'Cat tembok berkualitas tinggi'
            ],
            [
                'name' => 'Avitex Wall Paint',
                'code' => 'AVT-002',
                'category_id' => 1,
                'brand' => 'Avitex',
                'color' => 'Biru',
                'size' => '1L',
                'unit' => 'Kaleng',
                'minimum_stock' => 15,
                'current_stock' => 30,
                'purchase_price' => 45000,
                'selling_price' => 50000,
                'description' => 'Cat tembok ekonomis berkualitas'
            ],
            [
                'name' => 'Wood Stain Natural',
                'code' => 'WS-003',
                'category_id' => 2,
                'brand' => 'Biovarnish',
                'color' => 'Natural',
                'size' => '1L',
                'unit' => 'Kaleng',
                'minimum_stock' => 8,
                'current_stock' => 20,
                'purchase_price' => 65000,
                'selling_price' => 75000,
                'description' => 'Pewarna kayu alami'
            ],
            [
                'name' => 'Anti Rust Paint',
                'code' => 'AR-004',
                'category_id' => 3,
                'brand' => 'Rust Guard',
                'color' => 'Merah',
                'size' => '1L',
                'unit' => 'Kaleng',
                'minimum_stock' => 12,
                'current_stock' => 18,
                'purchase_price' => 55000,
                'selling_price' => 65000,
                'description' => 'Cat anti karat untuk logam'
            ],
            [
                'name' => 'Primer Sealer',
                'code' => 'PR-005',
                'category_id' => 4,
                'brand' => 'Base Coat',
                'color' => 'Transparan',
                'size' => '1L',
                'unit' => 'Kaleng',
                'minimum_stock' => 10,
                'current_stock' => 15,
                'purchase_price' => 40000,
                'selling_price' => 48000,
                'description' => 'Primer dasar untuk finishing'
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}