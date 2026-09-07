<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Skip if already seeded ────────────────────────────────────
        if (User::count() > 0) {
            $this->command->info('Database already seeded. Skipping.');
            return;
        }

        // ── Global Super Admin Accounts ────────────────────────────────
        User::create([
            'name' => 'Admin Owner',
            'email' => 'admin@inventory.ph',
            'password' => '123456789',
            'role' => User::ROLE_SUPER_ADMIN,
            'branch_id' => null,
        ]);

        // Canonical demo account referenced by README + Postman collection.
        User::create([
            'name' => 'Owner Admin',
            'email' => 'owner@inventory.test',
            'password' => '123456789',
            'role' => User::ROLE_SUPER_ADMIN,
            'branch_id' => null,
        ]);

        // ── 6 Operating Branches ───────────────────────────────────────

        $branches = collect([
            ['name' => 'Branch QC', 'location' => 'Quezon City, Metro Manila', 'latitude' => 14.6760, 'longitude' => 121.0437, 'status' => 'active'],
            ['name' => 'Branch Makati', 'location' => 'Makati City, Metro Manila', 'latitude' => 14.5547, 'longitude' => 121.0500, 'status' => 'active'],
            ['name' => 'Branch BGC', 'location' => 'Bonifacio Global City, Taguig', 'latitude' => 14.5510, 'longitude' => 121.0495, 'status' => 'active'],
            ['name' => 'Branch Cebu', 'location' => 'Cebu City, Cebu', 'latitude' => 10.3157, 'longitude' => 123.8854, 'status' => 'active'],
            ['name' => 'Branch Davao', 'location' => 'Davao City, Davao del Sur', 'latitude' => 7.1907, 'longitude' => 125.4553, 'status' => 'active'],
            ['name' => 'Branch Clark', 'location' => 'Clark Freeport Zone, Pampanga', 'latitude' => 15.1849, 'longitude' => 120.5566, 'status' => 'active'],
        ])->map(fn ($data) => Branch::create($data));

        // Canonical demo manager referenced by README + Postman collection.
        User::create([
            'name' => 'Manager Branch QC',
            'email' => 'manager@inventory.test',
            'password' => '123456789',
            'role' => User::ROLE_MANAGER,
            'branch_id' => $branches[0]->id,
        ]);

        // Demo staff account (pin login) for role-gate testing.
        User::create([
            'name' => 'Staff Juan',
            'pin' => '123456789',
            'role' => User::ROLE_STAFF,
            'branch_id' => $branches[0]->id,
        ]);

        // ── Helper to build the demo profile for a given user ──────────
        $profileDataMap = [
            'Maria Santos' => [
                'phone' => '+63 912 345 6789', 'address' => '123 Katipunan Ave, Quezon City', 'birthday' => 'March 15, 1998',
                'senior_high' => 'Quezon City Science HS', 'college' => 'UP Diliman — BS Nutrition',
                'partner_contact' => '+63 917 654 3210', 'mother_contact' => '+63 908 777 8888',
                'skills' => ['Barista', 'Chef', 'Marketing'],
                'notes' => 'Allergies: Pollen — Severe',
                'work_schedule' => ['Mon'=>'10:00 AM — 8:00 PM','Tue'=>'10:00 AM — 8:00 PM','Wed'=>'10:00 AM — 8:00 PM','Thu'=>'10:00 AM — 8:00 PM','Fri'=>'10:00 AM — 8:00 PM'],
                'performance_metrics' => ['Always on time','Good customer service','Fast worker','Team player','High accuracy on orders'],
            ],
            'Juan dela Cruz' => [
                'phone' => '+63 923 456 7890', 'address' => '456 Shaw Blvd, Mandaluyong City', 'birthday' => 'June 10, 1997',
                'senior_high' => 'Mandaluyong Science HS', 'college' => 'UST — BS Hotel & Restaurant Mgmt',
                'partner_contact' => '+63 927 111 2222', 'mother_contact' => '+63 902 333 4444',
                'skills' => ['Chef', 'Baking', 'Inventory'],
                'notes' => 'Food handling cert. expires Dec 2026',
                'work_schedule' => ['Mon'=>'10:00 AM — 8:00 PM','Tue'=>'10:00 AM — 8:00 PM','Wed'=>'10:00 AM — 8:00 PM','Thu'=>'10:00 AM — 8:00 PM','Fri'=>'10:00 AM — 8:00 PM'],
                'performance_metrics' => ['Excellent cook','Good inventory management','Team player'],
            ],
            'Ana Reyes' => [
                'phone' => '+63 934 567 8901', 'address' => '789 Paseo de Roxas, Makati City', 'birthday' => 'September 22, 1999',
                'senior_high' => 'Makati Science HS', 'college' => 'DLSU — BS Accountancy',
                'partner_contact' => '+63 917 555 6666', 'mother_contact' => '+63 905 777 8888',
                'skills' => ['Cashiering', 'Customer Service', 'Basic Barista'],
                'notes' => 'Cash bond on file: ₱5,000',
                'work_schedule' => ['Mon'=>'10:00 AM — 8:00 PM','Tue'=>'10:00 AM — 8:00 PM','Wed'=>'10:00 AM — 8:00 PM','Thu'=>'10:00 AM — 8:00 PM','Fri'=>'10:00 AM — 8:00 PM'],
                'performance_metrics' => ['Very reliable','Good with customers','Fast cashier'],
            ],
            'Pedro Gonzales' => [
                'phone' => '+63 945 678 9012', 'address' => '321 Ayala Ave, Makati City', 'birthday' => 'January 5, 1996',
                'senior_high' => 'Ateneo de Manila SHS', 'college' => 'ADMU — BS Marketing',
                'partner_contact' => '+63 927 888 9999', 'mother_contact' => '+63 908 111 2222',
                'skills' => ['Marketing', 'Social Media', 'Photography'],
                'notes' => 'Handles all branch social media accounts',
                'work_schedule' => ['Mon'=>'9:00 AM — 6:00 PM','Tue'=>'9:00 AM — 6:00 PM','Wed'=>'9:00 AM — 6:00 PM','Thu'=>'9:00 AM — 6:00 PM','Fri'=>'9:00 AM — 6:00 PM'],
                'performance_metrics' => ['Creative campaigns','Good social media presence','Team player'],
            ],
            'Luisa Tan' => [
                'phone' => '+63 956 789 0123', 'address' => '654 Bonifacio High St, BGC', 'birthday' => 'November 12, 2000',
                'senior_high' => 'BGC International SHS', 'college' => 'UP BGC — BS Business Admin',
                'partner_contact' => '+63 917 444 5555', 'mother_contact' => '+63 905 666 7777',
                'skills' => ['Barista', 'Latte Art', 'Pastry'],
                'notes' => 'Brewing competition finalist 2025',
                'work_schedule' => ['Mon'=>'10:00 AM — 8:00 PM','Tue'=>'10:00 AM — 8:00 PM','Wed'=>'10:00 AM — 8:00 PM','Thu'=>'10:00 AM — 8:00 PM','Fri'=>'10:00 AM — 8:00 PM'],
                'performance_metrics' => ['Award-winning barista','Fast worker','Great sales upsell'],
            ],
        ];

        $seedProfile = function (User $user) use ($profileDataMap) {
            $data = $profileDataMap[$user->name] ?? [];
            if (! empty($data)) {
                $user->profile()->create($data);
            }
        };

        // ── Staff Accounts (single account per person with both PIN + email) ──

        $staffData = [
            ['name' => 'Maria Santos',      'pin' => '123456789', 'email' => 'maria.santos@nita.com',     'branch' => 0], // QC
            ['name' => 'Juan dela Cruz',    'pin' => '123456789', 'email' => 'juan.delacruz@nita.com',   'branch' => 0], // QC
            ['name' => 'Ana Reyes',          'pin' => '123456789', 'email' => 'ana.reyes@nita.com',       'branch' => 1], // Makati
            ['name' => 'Pedro Gonzales',     'pin' => '123456789', 'email' => 'pedro.gonzales@nita.com',  'branch' => 1], // Makati
            ['name' => 'Luisa Tan',          'pin' => '123456789', 'email' => 'luisa.tan@nita.com',       'branch' => 2], // BGC
        ];

        foreach ($staffData as $staff) {
            $u = User::create([
                'name' => $staff['name'],
                'pin' => $staff['pin'],
                'email' => $staff['email'],
                'password' => '123456789',
                'role' => User::ROLE_STAFF,
                'branch_id' => $branches[$staff['branch']]->id,
            ]);
            $seedProfile($u);
        }

        // ── Branch Manager Accounts (single account per person with both PIN + email) ──

        $managerData = [
            ['name' => 'Juan Cruz',          'pin' => '123456789', 'email' => 'juan.cruz@nita.com',        'branch' => 0], // QC
            ['name' => 'Maria Santos',       'pin' => '123456789', 'email' => 'maria.santos.mgr@nita.com',  'branch' => 0], // QC
            ['name' => 'Pedro Reyes',        'pin' => '123456789', 'email' => 'pedro.reyes@nita.com',      'branch' => 1], // Makati
            ['name' => 'Ana Gonzales',       'pin' => '123456789', 'email' => 'ana.gonzales@nita.com',     'branch' => 1], // Makati
            ['name' => 'Jose Mercado',       'pin' => '123456789', 'email' => 'jose.mercado@nita.com',     'branch' => 2], // BGC
            ['name' => 'Luisa Fernandez',    'pin' => '123456789', 'email' => 'luisa.fernandez@nita.com',  'branch' => 2], // BGC
            ['name' => 'Carlos Ramos',       'pin' => '123456789', 'email' => 'carlos.ramos@nita.com',     'branch' => 3], // Cebu
            ['name' => 'Elena Torres',       'pin' => '123456789', 'email' => 'elena.torres@nita.com',     'branch' => 4], // Davao
            ['name' => 'Miguel Villanueva',  'pin' => '123456789', 'email' => 'miguel.villanueva@nita.com', 'branch' => 4], // Davao
            ['name' => 'Sofia Lim',          'pin' => '123456789', 'email' => 'sofia.lim@nita.com',        'branch' => 5], // Clark
        ];

        foreach ($managerData as $manager) {
            $u = User::create([
                'name' => $manager['name'],
                'pin' => $manager['pin'],
                'email' => $manager['email'],
                'password' => '123456789',
                'role' => User::ROLE_MANAGER,
                'branch_id' => $branches[$manager['branch']]->id,
            ]);
            $seedProfile($u);
        }

        // ── Ingredients — raw materials master list ────────────────────

        $ingredients = collect([
            'flavor_powder' => Ingredient::create(['name' => 'Flavor Powder', 'unit' => 'g']),
            'milk_tea_cup' => Ingredient::create(['name' => 'Milk Tea Cup', 'unit' => 'pcs']),
            'cup_wrapper' => Ingredient::create(['name' => 'Cup Wrapper', 'unit' => 'pcs']),
            'tapioca_pearls' => Ingredient::create(['name' => 'Tapioca Pearls', 'unit' => 'g']),
            'siomai_wrapper' => Ingredient::create(['name' => 'Siomai Wrapper', 'unit' => 'pcs']),
            'pork_filling' => Ingredient::create(['name' => 'Pork Filling', 'unit' => 'g']),
            'steamer_cup' => Ingredient::create(['name' => 'Steamer Cup', 'unit' => 'pcs']),
            'flour' => Ingredient::create(['name' => 'Flour', 'unit' => 'g']),
            'sugar' => Ingredient::create(['name' => 'Sugar', 'unit' => 'g']),
            'butter' => Ingredient::create(['name' => 'Butter', 'unit' => 'g']),
        ]);

        // ── Menu + recipes (formula per unit sold, per size) ────────────
        // POS sales currently deduct the "regular" formula only — "large" is
        // formula-configuration data for the Recipe tab until the tablet POS
        // supports a size selector.

        $classicMilkTea = Product::create([
            'name' => 'Classic Milk Tea',
            'category' => 'Milk Tea',
            'price' => 65.00,
            'procedure' => "Brew the tea: Steep tea leaves in hot water for 5 minutes, then let cool.\nMix: Combine brewed tea with flavor powder and shake well.\nAssemble: Pour into cup with ice, seal with cup wrapper, serve.",
        ]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['flavor_powder']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 30]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['milk_tea_cup']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['cup_wrapper']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['flavor_powder']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 45]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['milk_tea_cup']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $classicMilkTea->id, 'ingredient_id' => $ingredients['cup_wrapper']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 1]);

        $taroMilkTea = Product::create([
            'name' => 'Taro Milk Tea',
            'category' => 'Milk Tea',
            'price' => 70.00,
            'procedure' => "Cook the pearls: Boil 2 cups of water. Add pearls and simmer for 5-7 minutes. Drain water.\nMake it sweet: Mix cooked pearls with sugar and a tablespoon of water. Simmer on low heat until thick and glossy.\nAssemble: Combine taro powder with milk, add pearls, seal with cup wrapper, serve.",
        ]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['flavor_powder']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 25]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['tapioca_pearls']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 20]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['milk_tea_cup']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['cup_wrapper']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['flavor_powder']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 35]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['tapioca_pearls']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 30]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['milk_tea_cup']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 1]);
        Recipe::create(['product_id' => $taroMilkTea->id, 'ingredient_id' => $ingredients['cup_wrapper']->id, 'size' => Recipe::SIZE_LARGE, 'quantity_required' => 1]);

        $porkSiomai = Product::create([
            'name' => 'Pork Siomai (4pcs)',
            'category' => 'Siomai',
            'price' => 55.00,
            'procedure' => "Prep filling: Mix pork filling with seasoning until well combined.\nWrap: Spoon filling into siomai wrapper, pleat sides, shape into a cup.\nSteam: Arrange in steamer cup, steam for 15-20 minutes until cooked through.",
        ]);
        Recipe::create(['product_id' => $porkSiomai->id, 'ingredient_id' => $ingredients['siomai_wrapper']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 4]);
        Recipe::create(['product_id' => $porkSiomai->id, 'ingredient_id' => $ingredients['pork_filling']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 120]);
        Recipe::create(['product_id' => $porkSiomai->id, 'ingredient_id' => $ingredients['steamer_cup']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 1]);

        $pandesal = Product::create([
            'name' => 'Pandesal (5pcs)',
            'category' => 'Bakery',
            'price' => 40.00,
            'procedure' => "Mix dough: Combine flour, sugar, and butter, knead until smooth.\nProof: Let dough rise for 1 hour until doubled in size.\nShape & bake: Divide into 5 pieces, coat in breadcrumbs, bake at 180C for 15-18 minutes.",
        ]);
        Recipe::create(['product_id' => $pandesal->id, 'ingredient_id' => $ingredients['flour']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 200]);
        Recipe::create(['product_id' => $pandesal->id, 'ingredient_id' => $ingredients['sugar']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 30]);
        Recipe::create(['product_id' => $pandesal->id, 'ingredient_id' => $ingredients['butter']->id, 'size' => Recipe::SIZE_REGULAR, 'quantity_required' => 20]);

        // ── Starting stock — every ingredient stocked at every branch ──

        $startingStock = [
            'flavor_powder' => ['qty' => 2000, 'min' => 300],
            'milk_tea_cup' => ['qty' => 300, 'min' => 50],
            'cup_wrapper' => ['qty' => 300, 'min' => 50],
            'tapioca_pearls' => ['qty' => 1500, 'min' => 200],
            'siomai_wrapper' => ['qty' => 400, 'min' => 60],
            'pork_filling' => ['qty' => 5000, 'min' => 500],
            'steamer_cup' => ['qty' => 150, 'min' => 30],
            'flour' => ['qty' => 4000, 'min' => 500],
            'sugar' => ['qty' => 1000, 'min' => 150],
            'butter' => ['qty' => 800, 'min' => 100],
        ];

        foreach ($branches as $branch) {
            foreach ($startingStock as $key => $levels) {
                BranchStock::create([
                    'branch_id' => $branch->id,
                    'ingredient_id' => $ingredients[$key]->id,
                    'current_quantity' => $levels['qty'],
                    'min_threshold' => $levels['min'],
                ]);
            }
        }

        // ── Suppliers (only if empty) ──────────────────────────────
        $this->call(SupplierSeeder::class);

        // ── Meetings (only if empty) ─────────────────────────────────
        $this->call(MeetingSeeder::class);

        // ── Sample data: customers, POs, equipment, etc. ────────────
        $this->call(SampleDataSeeder::class);

        // ── Live data: transactions, payments, alerts, etc. ──────────
        $this->call(LiveDataSeeder::class);
    }
}
