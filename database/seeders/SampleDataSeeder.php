<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerFeedback;
use App\Models\Equipment;
use App\Models\Ingredient;
use App\Models\LeaveRequest;
use App\Models\PurchaseHistory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SafetyChecklist;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::where('status', 'active')->get();
        $ingredients = Ingredient::all();
        $suppliers = Supplier::all();
        $staff = User::whereIn('role', ['staff', 'manager'])->get();

        if ($branches->isEmpty() || $ingredients->isEmpty()) {
            $this->command->warn('Need at least one branch and ingredients. Run DatabaseSeeder first.');
            return;
        }

        $this->command->info('Seeding sample data...');

        // ═══ CUSTOMERS ═══
        $this->command->info('  Creating customers...');
        $customerNames = [
            ['name' => 'Maria Santos', 'email' => 'maria.santos@email.com', 'phone' => '+63 917 123 4567'],
            ['name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@email.com', 'phone' => '+63 918 234 5678'],
            ['name' => 'Ana Reyes', 'email' => 'ana.reyes@email.com', 'phone' => '+63 919 345 6789'],
            ['name' => 'Carlos Garcia', 'email' => 'carlos.garcia@email.com', 'phone' => '+63 920 456 7890'],
            ['name' => 'Sofia Lim', 'email' => 'sofia.lim@email.com', 'phone' => '+63 921 567 8901'],
            ['name' => 'Miguel Torres', 'email' => 'miguel.torres@email.com', 'phone' => '+63 922 678 9012'],
            ['name' => 'Isabella Cruz', 'email' => 'isabella.cruz@email.com', 'phone' => '+63 923 789 0123'],
            ['name' => 'Daniel Mendoza', 'email' => 'daniel.mendoza@email.com', 'phone' => '+63 924 890 1234'],
            ['name' => 'Camille Tan', 'email' => 'camille.tan@email.com', 'phone' => '+63 925 901 2345'],
            ['name' => 'Patrick Villanueva', 'email' => 'patrick.v@email.com', 'phone' => '+63 926 012 3456'],
        ];

        foreach ($customerNames as $i => $data) {
            $branch = $branches->random();
            $points = [0, 50, 150, 250, 400, 600, 800, 1200][$i % 8];
            $tier = match(true) {
                $points >= 1000 => 'platinum',
                $points >= 500 => 'gold',
                $points >= 200 => 'silver',
                default => 'bronze',
            };

            Customer::create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'loyalty_points' => $points,
                'tier' => $tier,
                'total_spent' => $points * 2.5,
                'total_visits' => rand(5, 50),
                'last_visit_at' => Carbon::now()->subDays(rand(0, 14)),
            ]);
        }

        // ═══ LEAVE REQUESTS ═══
        $this->command->info('  Creating leave requests...');
        if ($staff->isNotEmpty()) {
            $leaveTypes = ['sick', 'vacation', 'personal', 'emergency'];
            $statuses = ['pending', 'approved', 'approved', 'rejected'];
            $reasons = [
                'Feeling unwell, need to rest',
                'Family vacation planned',
                'Personal appointment',
                'Emergency at home',
                'Medical checkup',
                'Attending a wedding',
                'Family gathering',
            ];

            for ($i = 0; $i < 8; $i++) {
                $employee = $staff->random();
                $startDate = Carbon::now()->addDays(rand(-10, 20));
                $endDate = $startDate->copy()->addDays(rand(1, 5));
                $status = $statuses[array_rand($statuses)];

                LeaveRequest::create([
                    'user_id' => $employee->id,
                    'branch_id' => $employee->branch_id,
                    'type' => $leaveTypes[array_rand($leaveTypes)],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $status,
                    'reviewed_by' => $status !== 'pending' ? $staff->firstWhere('role', 'manager')?->id : null,
                ]);
            }
        }

        // ═══ PURCHASE ORDERS ═══
        $this->command->info('  Creating purchase orders...');
        if ($suppliers->isNotEmpty()) {
            $statuses = ['draft', 'pending', 'approved', 'ordered', 'delivered', 'received'];

            for ($i = 0; $i < 10; $i++) {
                $branch = $branches->random();
                $supplier = $suppliers->random();
                $status = $statuses[array_rand($statuses)];
                $poNumber = 'PO-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);

                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'branch_id' => $branch->id,
                    'supplier_id' => $supplier->id,
                    'created_by' => $staff->firstWhere('role', 'manager')?->id ?? $staff->first()?->id,
                    'status' => $status,
                    'expected_delivery' => Carbon::now()->addDays(rand(3, 14)),
                    'notes' => $i % 3 === 0 ? 'Urgent order' : null,
                ]);

                // Add 2-4 items to each PO
                $itemCount = rand(2, 4);
                $usedIngredients = [];
                for ($j = 0; $j < $itemCount; $j++) {
                    $ingredient = $ingredients->random();
                    while (in_array($ingredient->id, $usedIngredients)) {
                        $ingredient = $ingredients->random();
                    }
                    $usedIngredients[] = $ingredient->id;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => rand(10, 100),
                        'unit_price' => rand(50, 500) / 10,
                    ]);
                }

                $po->recalculateTotal();
            }
        }

        // ═══ EQUIPMENT ═══
        $this->command->info('  Creating equipment...');
        $equipmentItems = [
            ['name' => 'Espresso Machine #1', 'category' => 'beverage'],
            ['name' => 'Espresso Machine #2', 'category' => 'beverage'],
            ['name' => 'Commercial Blender', 'category' => 'beverage'],
            ['name' => 'Refrigerator Unit A', 'category' => 'kitchen'],
            ['name' => 'Refrigerator Unit B', 'category' => 'kitchen'],
            ['name' => 'Freezer Chest', 'category' => 'kitchen'],
            ['name' => 'Dishwasher', 'category' => 'cleaning'],
            ['name' => 'Ice Maker', 'category' => 'beverage'],
            ['name' => 'POS Terminal', 'category' => 'other'],
            ['name' => 'Coffee Grinder', 'category' => 'beverage'],
            ['name' => 'Display Case', 'category' => 'furniture'],
            ['name' => 'Water Filtration System', 'category' => 'kitchen'],
        ];

        foreach ($equipmentItems as $i => $data) {
            $branch = $branches->random();
            $status = $i < 2 ? 'maintenance_needed' : ($i < 3 ? 'out_of_service' : 'active');

            Equipment::create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'category' => $data['category'],
                'serial_number' => 'SN-' . strtoupper(substr(md5($data['name']), 0, 8)),
                'purchase_date' => Carbon::now()->subMonths(rand(6, 36)),
                'warranty_expiry' => Carbon::now()->addMonths(rand(6, 24)),
                'last_maintenance' => Carbon::now()->subDays(rand(10, 90)),
                'next_maintenance' => Carbon::now()->addDays(rand(-5, 30)),
                'status' => $status,
                'notes' => $status === 'maintenance_needed' ? 'Needs cleaning and calibration' : null,
            ]);
        }

        // ═══ SAFETY CHECKLISTS ═══
        $this->command->info('  Creating safety checklists...');
        foreach ($branches->take(2) as $branch) {
            $items = SafetyChecklist::defaultItems();
            // Randomly pass/fail some items
            foreach ($items as &$item) {
                $item['passed'] = rand(0, 100) > 20; // 80% pass rate
            }

            SafetyChecklist::create([
                'branch_id' => $branch->id,
                'completed_by' => $staff->firstWhere('role', 'manager')?->id ?? $staff->first()?->id,
                'check_date' => Carbon::now()->subDays(rand(0, 5)),
                'status' => collect($items)->where('passed', true)->count() === count($items) ? 'pass' : 'partial',
                'items' => $items,
                'overall_notes' => 'Routine safety check completed.',
            ]);
        }

        // ═══ PURCHASE HISTORY (for P&L data) ═══
        $this->command->info('  Creating purchase history...');
        if ($suppliers->isNotEmpty()) {
            for ($i = 0; $i < 30; $i++) {
                $ingredient = $ingredients->random();
                $supplier = $suppliers->random();

                PurchaseHistory::create([
                    'ingredient_id' => $ingredient->id,
                    'supplier_id' => $supplier->id,
                    'unit_price' => rand(50, 500) / 10,
                    'quantity' => rand(10, 100),
                    'purchased_at' => Carbon::now()->subDays(rand(0, 60)),
                    'recorded_by' => $staff->first()?->id,
                ]);
            }
        }

        // ═══ CUSTOMER FEEDBACK ═══
        $this->command->info('  Creating customer feedback...');
        $customers = Customer::all();
        $feedbackCategories = ['service', 'food', 'ambiance', 'value'];
        $feedbackComments = [
            'Great service! The staff was very friendly.',
            'The food was delicious, will definitely come back.',
            'Loved the ambiance, perfect for working.',
            'Good value for money, portions are generous.',
            'The coffee was amazing, best in town!',
            'Quick service, my order was ready in no time.',
            'Nice place to hang out with friends.',
            'The pastries were fresh and tasty.',
        ];

        foreach ($customers->take(6) as $customer) {
            $rating = rand(3, 5);
            CustomerFeedback::create([
                'customer_id' => $customer->id,
                'branch_id' => $customer->branch_id,
                'rating' => $rating,
                'category' => $feedbackCategories[array_rand($feedbackCategories)],
                'comment' => $feedbackComments[array_rand($feedbackComments)],
            ]);
            $customer->addLoyaltyPoints(10); // Bonus for feedback
        }

        $this->command->info('✅ Sample data seeded successfully!');
        $this->command->info('');
        $this->command->info('New data created:');
        $this->command->info('  • ' . Customer::count() . ' customers with loyalty tiers');
        $this->command->info('  • ' . LeaveRequest::count() . ' leave requests');
        $this->command->info('  • ' . PurchaseOrder::count() . ' purchase orders');
        $this->command->info('  • ' . Equipment::count() . ' equipment items');
        $this->command->info('  • ' . SafetyChecklist::count() . ' safety checklists');
        $this->command->info('  • ' . PurchaseHistory::count() . ' purchase history records');
        $this->command->info('  • ' . CustomerFeedback::count() . ' customer feedback');
    }
}
