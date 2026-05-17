<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\PaymentLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDefaultCategories();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $cashWallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'currency' => 'PKR',
            'balance' => 50000,
            'color' => '#10b981',
            'icon' => 'banknotes',
            'is_default' => true,
        ]);

        $bankWallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'Bank Account',
            'type' => 'bank',
            'currency' => 'PKR',
            'balance' => 150000,
            'color' => '#6366f1',
            'icon' => 'building-library',
            'is_default' => false,
        ]);

        $defaultCategories = Category::whereNull('user_id')->get();

        $this->seedMerchantsAndTransactions($user, $cashWallet, $bankWallet, $defaultCategories);
        $this->seedNotifications($user);
        $this->seedPaymentHistory($user);
    }

    private function seedDefaultCategories(): void
    {
        $categories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'color' => '#f59e0b', 'icon' => 'utensils'],
            ['name' => 'Transport', 'type' => 'expense', 'color' => '#3b82f6', 'icon' => 'car'],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => '#ec4899', 'icon' => 'shopping-bag'],
            ['name' => 'Bills & Utilities', 'type' => 'expense', 'color' => '#ef4444', 'icon' => 'receipt'],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#8b5cf6', 'icon' => 'film'],
            ['name' => 'Health', 'type' => 'expense', 'color' => '#10b981', 'icon' => 'heart'],
            ['name' => 'Education', 'type' => 'expense', 'color' => '#06b6d4', 'icon' => 'academic-cap'],
            ['name' => 'Travel', 'type' => 'expense', 'color' => '#f97316', 'icon' => 'airplane'],
            ['name' => 'Salary', 'type' => 'income', 'color' => '#22c55e', 'icon' => 'briefcase'],
            ['name' => 'Freelance', 'type' => 'income', 'color' => '#14b8a6', 'icon' => 'computer-desktop'],
            ['name' => 'Investment', 'type' => 'income', 'color' => '#a855f7', 'icon' => 'chart-bar'],
            ['name' => 'Other', 'type' => 'both', 'color' => '#6b7280', 'icon' => 'tag'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name'], 'user_id' => null], $cat + ['is_default' => true]);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Category>  $categories
     */
    private function seedMerchantsAndTransactions(User $user, Wallet $cashWallet, Wallet $bankWallet, $categories): void
    {
        $merchantData = [
            ['name' => 'Carrefour', 'category' => 'Shopping'],
            ['name' => 'KFC', 'category' => 'Food & Dining'],
            ['name' => 'McDonald\'s', 'category' => 'Food & Dining'],
            ['name' => 'Shell', 'category' => 'Transport'],
            ['name' => 'Netflix', 'category' => 'Entertainment'],
            ['name' => 'LUMS Hospital', 'category' => 'Health'],
            ['name' => 'Daraz', 'category' => 'Shopping'],
            ['name' => 'PTCL', 'category' => 'Bills & Utilities'],
        ];

        $merchants = collect($merchantData)->map(function (array $data) use ($user, $categories) {
            $category = $categories->firstWhere('name', $data['category']);

            return Merchant::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'category_id' => $category?->id,
                'logo' => null,
            ]);
        });

        $expenseCategory = $categories->firstWhere('name', 'Food & Dining');
        $salaryCategory = $categories->firstWhere('name', 'Salary');
        $freelanceCategory = $categories->firstWhere('name', 'Freelance');
        $transportCategory = $categories->firstWhere('name', 'Transport');
        $shoppingCategory = $categories->firstWhere('name', 'Shopping');
        $billsCategory = $categories->firstWhere('name', 'Bills & Utilities');

        // Monthly salary income for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $bankWallet->id,
                'category_id' => $salaryCategory?->id,
                'merchant_id' => null,
                'type' => 'income',
                'amount' => 85000,
                'currency' => 'PKR',
                'description' => 'Monthly salary',
                'date' => now()->subMonths($i)->startOfMonth()->format('Y-m-d'),
                'is_recurring' => true,
            ]);
        }

        // Freelance income
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $bankWallet->id,
            'category_id' => $freelanceCategory?->id,
            'type' => 'income',
            'amount' => 25000,
            'currency' => 'PKR',
            'description' => 'Website project payment',
            'date' => now()->subMonths(1)->subDays(5)->format('Y-m-d'),
        ]);

        // Regular expense transactions
        $expenseTransactions = [
            ['wallet' => $cashWallet, 'category' => $expenseCategory, 'merchant' => 'KFC', 'amount' => 2800, 'description' => 'Dinner with family', 'daysAgo' => 2],
            ['wallet' => $cashWallet, 'category' => $expenseCategory, 'merchant' => 'McDonald\'s', 'amount' => 1500, 'description' => 'Lunch', 'daysAgo' => 4],
            ['wallet' => $bankWallet, 'category' => $shoppingCategory, 'merchant' => 'Carrefour', 'amount' => 12000, 'description' => 'Grocery shopping', 'daysAgo' => 6],
            ['wallet' => $cashWallet, 'category' => $transportCategory, 'merchant' => 'Shell', 'amount' => 6000, 'description' => 'Fuel', 'daysAgo' => 7],
            ['wallet' => $bankWallet, 'category' => $shoppingCategory, 'merchant' => 'Daraz', 'amount' => 8500, 'description' => 'Online order', 'daysAgo' => 10],
            ['wallet' => $bankWallet, 'category' => $billsCategory, 'merchant' => 'PTCL', 'amount' => 2500, 'description' => 'Internet bill', 'daysAgo' => 12],
            ['wallet' => $cashWallet, 'category' => $expenseCategory, 'merchant' => 'KFC', 'amount' => 3200, 'description' => 'Team lunch', 'daysAgo' => 15],
            ['wallet' => $bankWallet, 'category' => $shoppingCategory, 'merchant' => 'Carrefour', 'amount' => 9500, 'description' => 'Weekly groceries', 'daysAgo' => 20],
            ['wallet' => $cashWallet, 'category' => $transportCategory, 'merchant' => 'Shell', 'amount' => 5500, 'description' => 'Fuel fill-up', 'daysAgo' => 25],
            ['wallet' => $bankWallet, 'category' => $billsCategory, 'merchant' => 'PTCL', 'amount' => 2500, 'description' => 'Internet bill', 'daysAgo' => 42],
            ['wallet' => $bankWallet, 'category' => $shoppingCategory, 'merchant' => 'Daraz', 'amount' => 5200, 'description' => 'Electronics', 'daysAgo' => 50],
            ['wallet' => $cashWallet, 'category' => $expenseCategory, 'merchant' => 'McDonald\'s', 'amount' => 1800, 'description' => 'Breakfast', 'daysAgo' => 55],
        ];

        foreach ($expenseTransactions as $txData) {
            $merchant = $merchants->firstWhere('name', $txData['merchant']);

            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $txData['wallet']->id,
                'category_id' => $txData['category']?->id,
                'merchant_id' => $merchant?->id,
                'type' => 'expense',
                'amount' => $txData['amount'],
                'currency' => 'PKR',
                'description' => $txData['description'],
                'date' => now()->subDays($txData['daysAgo'])->format('Y-m-d'),
            ]);
        }

        // A few random additional transactions
        Transaction::factory()
            ->count(20)
            ->expense()
            ->forUser($user, $cashWallet)
            ->state(['category_id' => $expenseCategory?->id])
            ->create();

        Transaction::factory()
            ->count(10)
            ->expense()
            ->forUser($user, $bankWallet)
            ->state(['category_id' => $shoppingCategory?->id])
            ->create();
    }

    private function seedNotifications(User $user): void
    {
        $notifications = [
            ['title' => 'Welcome to ExpenseBro!', 'body' => 'Start tracking your expenses to gain financial clarity.', 'type' => 'success'],
            ['title' => 'Salary received', 'body' => 'PKR 85,000 income recorded in Bank Account.', 'type' => 'success'],
            ['title' => 'Top spending: Food & Dining', 'body' => 'You\'ve spent PKR 8,500 on Food & Dining this month.', 'type' => 'info'],
            ['title' => 'Tip: Try AI Chat', 'body' => 'Upgrade to Now plan to log transactions via natural language.', 'type' => 'info'],
            ['title' => 'Large expense detected', 'body' => 'PKR 12,000 spent at Carrefour on ' . now()->subDays(6)->format('M j') . '.', 'type' => 'warning'],
        ];

        foreach ($notifications as $i => $n) {
            AppNotification::create([
                'user_id' => $user->id,
                'title' => $n['title'],
                'body' => $n['body'],
                'type' => $n['type'],
                'read' => $i >= 3,
                'created_at' => now()->subDays($i),
                'updated_at' => now()->subDays($i),
            ]);
        }
    }

    private function seedPaymentHistory(User $user): void
    {
        // Sample paid months for a "now" subscriber who cancelled
        $months = [3, 2, 1];
        foreach ($months as $monthsAgo) {
            PaymentLog::create([
                'user_id' => $user->id,
                'plan' => 'now',
                'amount' => 1.00,
                'currency' => 'USD',
                'status' => 'paid',
                'invoice_id' => 'INV-' . strtoupper(substr(md5($user->id . $monthsAgo), 0, 8)),
                'paid_at' => now()->subMonths($monthsAgo)->startOfMonth(),
            ]);
        }
    }
}
