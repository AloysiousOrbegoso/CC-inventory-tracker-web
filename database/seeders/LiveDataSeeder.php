<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DiscrepancyAlert;
use App\Models\Ingredient;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobApplicant;
use App\Models\JobOpening;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShiftLog;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LiveDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Transaction::count() > 0) {
            $this->command->info('Live data already seeded. Skipping.');
            return;
        }

        $branches = Branch::where('status', 'active')->get();
        $products = Product::all();
        $ingredients = Ingredient::all();
        $staff = User::where('role', 'staff')->get();
        $managers = User::where('role', 'manager')->get();
        $admin = User::where('role', 'super_admin')->first();

        if ($branches->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Need branches and products. Run DatabaseSeeder first.');
            return;
        }

        $this->command->info('Seeding live data...');

        // ═══ TRANSACTIONS (last 6 months of sales) ═══════════════════
        $this->command->info('  Creating transactions...');
        $now = Carbon::now();

        for ($daysAgo = 180; $daysAgo >= 0; $daysAgo--) {
            $date = $now->copy()->subDays($daysAgo);
            $dayOfWeek = $date->dayOfWeek;

            // Fewer sales on weekends (Sun=0), more on Fri/Sat
            $baseSales = match ($dayOfWeek) {
                0 => rand(2, 6),    // Sunday
                6 => rand(8, 18),   // Saturday
                5 => rand(10, 20),  // Friday
                default => rand(5, 14), // Mon-Thu
            };

            foreach ($branches as $branch) {
                $salesToday = (int) round($baseSales * (0.7 + (mt_rand(0, 60) / 100)));

                for ($s = 0; $s < $salesToday; $s++) {
                    $product = $products->random();
                    $qty = random_int(1, 3);
                    $unitPrice = (float) $product->price;
                    $total = $unitPrice * $qty;

                    // Spread transactions throughout business hours (8am-9pm)
                    $hour = random_int(8, 20);
                    $minute = random_int(0, 59);

                    Transaction::create([
                        'client_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                        'branch_id' => $branch->id,
                        'user_id' => ($staff->where('branch_id', $branch->id)->first() ?? $staff->random())->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'total_amount' => $total,
                        'created_at' => $date->copy()->setTime($hour, $minute),
                    ]);
                }
            }
        }

        // ═══ PAYMENTS (expenses) ══════════════════════════════════════
        $this->command->info('  Creating payments...');
        $paymentCategories = ['rent', 'utilities', 'supplier', 'salary', 'maintenance', 'other'];
        $paymentMethods = ['cash', 'bank_transfer', 'gcash', 'check'];
        $payees = [
            'Meralco Electric Company', 'Maynilad Water Services', 'Manila Water',
            'Smart Communications', 'Globe Telecom', 'PLDT Inc.',
            'Landlord — QC Branch', 'Landlord — Makati Branch', 'Landlord — BGC Branch',
            'Landlord — Cebu Branch', 'Landlord — Davao Branch', 'Landlord — Clark Branch',
            'GCash Payment Processing', 'BDO Corporate Services',
            'McDelivery Supplies', 'San Miguel Corporation', 'Universal Robina',
        ];

        for ($i = 0; $i < 40; $i++) {
            $branch = $branches->random();
            $daysAgo = random_int(0, 90);
            $date = $now->copy()->subDays($daysAgo);
            $status = $daysAgo > 30 ? 'paid' : ['pending', 'paid', 'paid', 'paid'][random_int(0, 3)];
            $amount = random_int(800, 45000) / 10;

            Payment::create([
                'branch_id' => $branch->id,
                'recorded_by' => $managers->where('branch_id', $branch->id)->first()?->id ?? $admin->id,
                'category' => $paymentCategories[array_rand($paymentCategories)],
                'payee' => $payees[array_rand($payees)],
                'amount' => $amount,
                'method' => $paymentMethods[array_rand($paymentMethods)],
                'status' => $status,
                'due_date' => $date->copy()->addDays(random_int(5, 30)),
                'paid_at' => $status === 'paid' ? $date->copy()->addDays(random_int(1, 14)) : null,
                'notes' => $i % 5 === 0 ? 'Monthly recurring payment' : null,
                'created_at' => $date,
            ]);
        }

        // ═══ DISCREPANCY ALERTS ══════════════════════════════════════
        $this->command->info('  Creating discrepancy alerts...');
        $alertTypes = ['stock_mismatch', 'shift_variance'];
        $severities = ['low', 'medium', 'high'];
        $statuses = ['pending', 'pending', 'pending', 'reviewed', 'dismissed'];

        for ($i = 0; $i < 15; $i++) {
            $branch = $branches->random();
            $ingredient = $ingredients->random();
            $expected = random_int(50, 500) / 10;
            $variancePct = random_int(2, 25) / 100;
            $actual = $expected * (1 - $variancePct);
            $severity = $variancePct > 0.15 ? 'high' : ($variancePct > 0.08 ? 'medium' : 'low');

            DiscrepancyAlert::create([
                'branch_id' => $branch->id,
                'type' => $alertTypes[array_rand($alertTypes)],
                'severity' => $severity,
                'ingredient_id' => $ingredient->id,
                'expected_value' => $expected,
                'actual_value' => round($actual, 3),
                'variance' => round($actual - $expected, 3),
                'details' => "Expected {$expected} {$ingredient->unit}, found " . round($actual, 1) . " {$ingredient->unit}.",
                'status' => $statuses[array_rand($statuses)],
                'created_at' => $now->copy()->subDays(random_int(0, 30))->setTime(random_int(8, 20), random_int(0, 59)),
            ]);
        }

        // ═══ NOTICES / MESSAGES ═══════════════════════════════════════
        $this->command->info('  Creating notices...');
        $notices = [
            ['title' => 'Holiday Schedule Update', 'body' => "Please note the adjusted operating hours during the holidays:\n\nDec 24: Open until 6PM only\nDec 25: CLOSED\nDec 30: Open regular hours\nDec 31: Open until 6PM only\nJan 1: CLOSED\n\nKindly coordinate with your branch manager for shift adjustments.", 'branch_id' => null],
            ['title' => 'New POS System Rollout', 'body' => "We're rolling out the new POS system starting next week. Training sessions will be scheduled per branch.\n\nPlease complete the online training module before your scheduled session. Check the Calendar for your branch's training date.", 'branch_id' => null],
            ['title' => 'Monthly Inventory Audit Reminder', 'body' => "This is a reminder that monthly inventory audits are due by the 5th of each month.\n\nPlease ensure all stock counts are completed and submitted before the deadline. Contact your manager if you need assistance.", 'branch_id' => null],
            ['title' => 'Employee Appreciation Day', 'body' => "Join us for our quarterly Employee Appreciation Day! 🎉\n\nDate: Next Friday, 2:00 PM\nLocation: Each branch (local celebration)\n\nFree snacks, games, and recognition awards. Don't miss it!", 'branch_id' => null],
            ['title' => 'New Menu Items Coming Soon', 'body' => "Exciting news! We're launching 3 new drinks next month:\n\n🥤 Matcha Latte — ₱75\n🍵 Hokkaido Milk Tea — ₱80\n🍓 Strawberry Cream Tea — ₱75\n\nRecipe cards and training materials will be distributed this week.", 'branch_id' => null],
            ['title' => 'Safety Protocol Update', 'body' => "Updated safety protocols effective immediately:\n\n1. All staff must wash hands every 30 minutes during shift\n2. Hair nets are now mandatory for all food prep areas\n3. Temperature logs must be completed hourly\n\nPlease review the full document in the Legal Papers section.", 'branch_id' => null],
        ];

        foreach ($notices as $i => $notice) {
            Notice::create([
                ...$notice,
                'posted_by' => $admin->id,
                'created_at' => $now->copy()->subDays(random_int(1, 30)),
            ]);
        }

        // ═══ INVOICES ═════════════════════════════════════════════════
        $this->command->info('  Creating invoices...');
        $invoiceStatuses = ['draft', 'sent', 'paid', 'paid', 'overdue'];
        $customerNames = ['ABC Corporation', 'XYZ Trading', 'Metro Manila Foods', 'Cebu Pacific Catering', 'Davao Fresh Mart', 'Isabella Bakery Supply', 'Rizal Catering Services'];

        for ($i = 0; $i < 12; $i++) {
            $branch = $branches->random();
            $subtotal = random_int(1500, 25000) / 10;
            $taxRate = 12;
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $discount = $i % 4 === 0 ? round($subtotal * 0.1, 2) : 0;
            $total = $subtotal + $taxAmount - $discount;
            $status = $invoiceStatuses[array_rand($invoiceStatuses)];
            $createdDaysAgo = random_int(0, 60);

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'branch_id' => $branch->id,
                'created_by' => $managers->where('branch_id', $branch->id)->first()?->id ?? $admin->id,
                'customer_name' => $customerNames[array_rand($customerNames)],
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount' => $discount,
                'total' => $total,
                'status' => $status,
                'due_date' => $now->copy()->subDays($createdDaysAgo)->addDays(30),
                'paid_at' => $status === 'paid' ? $now->copy()->subDays($createdDaysAgo)->addDays(random_int(1, 20)) : null,
                'created_at' => $now->copy()->subDays($createdDaysAgo),
            ]);

            // Add 1-3 line items
            $itemCount = random_int(1, 3);
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $qty = random_int(5, 50);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => (float) $product->price,
                ]);
            }
        }

        // ═══ HIRING: OPENINGS + APPLICANTS ═══════════════════════════
        $this->command->info('  Creating hiring data...');
        $openings = [
            ['title' => 'Barista — Full Time', 'branch_id' => $branches[0]->id, 'status' => 'open', 'description' => 'Prepare and serve coffee drinks. Maintain cleanliness of bar area.'],
            ['title' => 'Kitchen Staff — Part Time', 'branch_id' => $branches[1]->id, 'status' => 'open', 'description' => 'Assist in food preparation and kitchen maintenance.'],
            ['title' => 'Shift Supervisor', 'branch_id' => $branches[2]->id, 'status' => 'open', 'description' => 'Oversee daily operations, manage staff, handle customer complaints.'],
            ['title' => 'Cashier — Full Time', 'branch_id' => $branches[0]->id, 'status' => 'closed', 'description' => 'Handle POS transactions, manage cash drawer, assist customers.'],
            ['title' => 'Delivery Rider', 'branch_id' => $branches[3]->id, 'status' => 'open', 'description' => 'Deliver orders to customers within the area. Must have valid driver license.'],
        ];

        $applicantNames = [
            'Carlo Mendoza', 'Patricia Cruz', 'Miguel Santos', 'Angela Reyes',
            'Jerome Lim', 'Bianca Torres', 'Rafael Garcia', 'Christine Tan',
            'Kyle Villanueva', 'Samantha Cruz', 'Andre Mendoza', 'Nicole Ramos',
        ];
        $applicantStatuses = ['applied', 'shortlisted', 'interviewed', 'hired', 'rejected'];

        foreach ($openings as $openingData) {
            $opening = JobOpening::create([
                ...$openingData,
                'posted_by' => $admin->id,
                'created_at' => $now->copy()->subDays(random_int(5, 30)),
            ]);

            // Add 2-4 applicants per opening
            $applicantCount = random_int(2, 4);
            $usedNames = [];
            for ($a = 0; $a < $applicantCount; $a++) {
                do {
                    $name = $applicantNames[array_rand($applicantNames)];
                } while (in_array($name, $usedNames));
                $usedNames[] = $name;

                JobApplicant::create([
                    'job_opening_id' => $opening->id,
                    'name' => $name,
                    'email' => strtolower(str_replace(' ', '.', $name)) . '@email.com',
                    'phone' => '+63 9' . random_int(10, 99) . ' ' . random_int(100, 999) . ' ' . random_int(1000, 9999),
                    'status' => $applicantStatuses[array_rand($applicantStatuses)],
                    'notes' => $a === 0 ? 'Strong resume, good references.' : null,
                    'created_at' => $now->copy()->subDays(random_int(1, 20)),
                ]);
            }
        }

        // ═══ SHIFT LOGS ═══════════════════════════════════════════════
        $this->command->info('  Creating shift logs...');
        foreach ($staff->take(5) as $worker) {
            // Create a few closed shifts in the past
            for ($d = 1; $d <= 5; $d++) {
                $shiftDate = $now->copy()->subDays($d);
                $startHour = random_int(7, 10);
                $endHour = $startHour + random_int(6, 10);

                ShiftLog::create([
                    'branch_id' => $worker->branch_id,
                    'user_id' => $worker->id,
                    'status' => 'closed',
                    'shift_start' => $shiftDate->copy()->setTime($startHour, 0),
                    'shift_end' => $shiftDate->copy()->setTime(min($endHour, 23), random_int(0, 59)),
                    'opening_till_amount' => 2000.00,
                ]);
            }
        }

        // ═══ AUDIT LOGS ═══════════════════════════════════════════════
        $this->command->info('  Creating audit logs...');
        $auditActions = ['create', 'update', 'delete'];
        $auditModels = [
            'App\\Models\\Transaction' => 'Transaction',
            'App\\Models\\Payment' => 'Payment',
            'App\\Models\\Branch' => 'Branch',
            'App\\Models\\User' => 'User',
            'App\\Models\\Product' => 'Product',
            'App\\Models\\Ingredient' => 'Ingredient',
            'App\\Models\\PurchaseOrder' => 'PurchaseOrder',
        ];
        $auditDescriptions = [
            'create' => ['Created new record', 'Added entry to system', 'New item registered'],
            'update' => ['Updated record details', 'Modified existing entry', 'Changed status'],
            'delete' => ['Removed record from system', 'Archived entry', 'Soft-deleted item'],
        ];

        for ($i = 0; $i < 25; $i++) {
            $action = $auditActions[array_rand($auditActions)];
            $modelClass = array_keys($auditModels)[array_rand(array_keys($auditModels))];
            $modelLabel = $auditModels[$modelClass];

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => $action,
                'model_type' => $modelClass,
                'model_id' => random_int(1, 10),
                'old_values' => $action === 'update' ? ['status' => 'old_value'] : null,
                'new_values' => $action !== 'delete' ? ['status' => 'new_value'] : null,
                'description' => $auditDescriptions[$action][array_rand($auditDescriptions[$action])],
                'created_at' => $now->copy()->subDays(random_int(0, 30))->setTime(random_int(8, 20), random_int(0, 59)),
            ]);
        }

        $this->command->info('✅ Live data seeded successfully!');
        $this->command->info('');
        $this->command->info('New data created:');
        $this->command->info('  • ' . number_format(Transaction::count()) . ' transactions (6 months of sales)');
        $this->command->info('  • ' . Payment::count() . ' payments (expenses)');
        $this->command->info('  • ' . DiscrepancyAlert::count() . ' discrepancy alerts');
        $this->command->info('  • ' . Notice::count() . ' notices/messages');
        $this->command->info('  • ' . Invoice::count() . ' invoices');
        $this->command->info('  • ' . JobOpening::count() . ' job openings with applicants');
        $this->command->info('  • ' . ShiftLog::count() . ' shift logs');
        $this->command->info('  • ' . AuditLog::count() . ' audit log entries');
    }
}
