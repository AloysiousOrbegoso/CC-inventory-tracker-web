<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        if (Supplier::count() > 0) {
            $this->command->info('Suppliers already seeded. Skipping.');
            return;
        }

        $suppliers = [
            [
                'name' => 'Manila Dairy Supply',
                'contact_person' => 'Ricardo Santos',
                'contact_number' => '0917-555-1234',
                'address' => '123 Rizal Ave, Ermita, Manila',
                'landmark' => 'Near LRT Carriedo Station',
                'latitude' => 14.5895,
                'longitude' => 120.9740,
                'notes' => 'Delivers Mon-Fri. Min order P2,000.',
                'is_active' => true,
            ],
            [
                'name' => 'Quezon City Fresh Market',
                'contact_person' => 'Maria Cruz',
                'contact_number' => '0918-666-2345',
                'address' => '456 Commonwealth Ave, Quezon City',
                'landmark' => 'Beside Ever Gotesco Mall',
                'latitude' => 14.6942,
                'longitude' => 121.0720,
                'notes' => 'Open 4AM-12NN. Walk-in or delivery.',
                'is_active' => true,
            ],
            [
                'name' => 'Makati Trading Co.',
                'contact_person' => 'Jun Bautista',
                'contact_number' => '0927-777-3456',
                'address' => '789 Makati Ave, Poblacion, Makati City',
                'landmark' => 'Near Century City Mall',
                'latitude' => 14.5610,
                'longitude' => 121.0320,
                'notes' => 'Wholesale supplier. Delivery available for orders above P5,000.',
                'is_active' => true,
            ],
            [
                'name' => 'Cebu Cocoa Traders',
                'contact_person' => 'Ana Villanueva',
                'contact_number' => '0932-888-4567',
                'address' => '321 Osmeña Blvd, Cebu City',
                'landmark' => 'Near Carbon Market',
                'latitude' => 10.3090,
                'longitude' => 123.8920,
                'notes' => 'Specializes in cocoa, chocolate, and flavor powders.',
                'is_active' => true,
            ],
            [
                'name' => 'Davao Tapioca Works',
                'contact_person' => 'Pedro Mendoza',
                'contact_number' => '0946-999-5678',
                'address' => '654 Quirino Ave, Davao City',
                'landmark' => 'Near SM City Davao',
                'latitude' => 7.1830,
                'longitude' => 125.4520,
                'notes' => 'Factory-direct tapioca pearls. Bulk discounts available.',
                'is_active' => true,
            ],
            [
                'name' => 'Pampanga Sugar Mill',
                'contact_person' => 'Luisa Garcia',
                'contact_number' => '0905-111-6789',
                'address' => '99 MacArthur Hwy, San Fernando, Pampanga',
                'landmark' => 'Near Robinsons Starmills',
                'latitude' => 15.0340,
                'longitude' => 120.6870,
                'notes' => 'Local sugar producer. Competitive pricing for bulk orders.',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::create($data);
        }

        // Link some ingredients to suppliers with pricing data
        $ingredients = Ingredient::pluck('id', 'name')->toArray();

        $links = [
            'Flavor Powder' => ['supplier' => 'Cebu Cocoa Traders', 'unit_cost' => 1.20, 'is_primary' => true, 'package_size' => 25, 'package_unit' => 'kg', 'package_price' => 300.00, 'delivery_fee' => 150.00],
            'Milk Tea Cup' => ['supplier' => 'Manila Dairy Supply', 'unit_cost' => 3.50, 'is_primary' => true, 'package_size' => 100, 'package_unit' => 'pcs', 'package_price' => 350.00, 'delivery_fee' => 100.00],
            'Cup Wrapper' => ['supplier' => 'Manila Dairy Supply', 'unit_cost' => 1.80, 'is_primary' => true, 'package_size' => 200, 'package_unit' => 'pcs', 'package_price' => 360.00, 'delivery_fee' => 100.00],
            'Tapioca Pearls' => ['supplier' => 'Davao Tapioca Works', 'unit_cost' => 0.45, 'is_primary' => true, 'package_size' => 50, 'package_unit' => 'kg', 'package_price' => 22.50, 'delivery_fee' => 200.00],
            'Siomai Wrapper' => ['supplier' => 'QC Fresh Market', 'unit_cost' => 2.00, 'is_primary' => true, 'package_size' => 100, 'package_unit' => 'pcs', 'package_price' => 200.00, 'delivery_fee' => 80.00],
            'Pork Filling' => ['supplier' => 'QC Fresh Market', 'unit_cost' => 0.35, 'is_primary' => true, 'package_size' => 10, 'package_unit' => 'kg', 'package_price' => 350.00, 'delivery_fee' => 80.00],
            'Flour' => ['supplier' => 'Pampanga Sugar Mill', 'unit_cost' => 0.06, 'is_primary' => true, 'package_size' => 25, 'package_unit' => 'kg', 'package_price' => 15.00, 'delivery_fee' => 120.00],
            'Sugar' => ['supplier' => 'Pampanga Sugar Mill', 'unit_cost' => 0.04, 'is_primary' => true, 'package_size' => 50, 'package_unit' => 'kg', 'package_price' => 20.00, 'delivery_fee' => 120.00],
            'Butter' => ['supplier' => 'Makati Trading Co.', 'unit_cost' => 0.55, 'is_primary' => true, 'package_size' => 5, 'package_unit' => 'kg', 'package_price' => 275.00, 'delivery_fee' => 100.00],
        ];

        foreach ($links as $ingredientName => $link) {
            if (isset($ingredients[$ingredientName])) {
                $supplier = Supplier::where('name', $link['supplier'])->first();
                if ($supplier) {
                    DB::table('ingredient_supplier')->insert([
                        'ingredient_id' => $ingredients[$ingredientName],
                        'supplier_id' => $supplier->id,
                        'unit_cost' => $link['unit_cost'],
                        'is_primary' => $link['is_primary'],
                        'package_size' => $link['package_size'],
                        'package_unit' => $link['package_unit'],
                        'package_price' => $link['package_price'],
                        'delivery_fee' => $link['delivery_fee'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Seeded 6 suppliers with coordinates and ingredient links.');
    }
}
