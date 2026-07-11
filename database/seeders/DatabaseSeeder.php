<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Models\Tag;
use App\Enums\TransactionType;
use App\Enums\AccountType;
use App\Enums\BudgetPeriod;
use App\Enums\GoalStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Clean up existing test user
        User::where('email', 'test@example.com')->delete();

        // 2. Create target user (this triggers UserObserver to create 20 default categories)
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // 3. Load default categories from observer by name
        $categories = $user->categories()->get()->keyBy('name');

        // 4. Create custom categories
        $gymCategory = $user->categories()->create([
            'name' => 'Gym & Fitness',
            'type' => TransactionType::Expense,
            'color' => '#06b6d4',
            'icon' => 'dumbbell',
            'is_default' => false,
        ]);

        $gadgetsCategory = $user->categories()->create([
            'name' => 'Gadgets',
            'type' => TransactionType::Expense,
            'color' => '#a855f7',
            'icon' => 'laptop',
            'is_default' => false,
        ]);

        $youtubeCategory = $user->categories()->create([
            'name' => 'YouTube Adsense',
            'type' => TransactionType::Income,
            'color' => '#ef4444',
            'icon' => 'youtube',
            'is_default' => false,
        ]);

        // 5. Create tags
        $tagImportant = $user->tags()->create(['name' => 'Important', 'color' => '#ef4444']);
        $tagLeisure = $user->tags()->create(['name' => 'Leisure', 'color' => '#3b82f6']);
        $tagWork = $user->tags()->create(['name' => 'Work', 'color' => '#10b981']);
        $tagHealth = $user->tags()->create(['name' => 'Health', 'color' => '#ec4899']);

        // 6. Create 4 financial accounts
        $card = $user->accounts()->create([
            'name' => 'Bank Card',
            'type' => AccountType::Card,
            'currency_code' => 'EUR',
            'balance' => 0, // calculated later
            'color' => '#3b82f6',
            'icon' => 'credit-card',
        ]);

        $cash = $user->accounts()->create([
            'name' => 'Cash Wallet',
            'type' => AccountType::Cash,
            'currency_code' => 'EUR',
            'balance' => 0, // calculated later
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        $crypto = $user->accounts()->create([
            'name' => 'Crypto Wallet',
            'type' => AccountType::Crypto,
            'currency_code' => 'EUR',
            'balance' => 0, // calculated later
            'color' => '#f59e0b',
            'icon' => 'bitcoin',
        ]);

        $paypal = $user->accounts()->create([
            'name' => 'PayPal Account',
            'type' => AccountType::PayPal,
            'currency_code' => 'EUR',
            'balance' => 0, // calculated later
            'color' => '#2563eb',
            'icon' => 'paypal',
        ]);

        // 7. Seed Incomes
        $salaryTx = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Salary']->id,
            'type' => TransactionType::Income,
            'amount' => 5000.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(20),
            'comment' => 'Monthly Salary',
        ]);
        $salaryTx->tags()->attach($tagWork);

        $freelanceTx = $user->transactions()->create([
            'account_id' => $paypal->id,
            'category_id' => $categories['Freelance']->id,
            'type' => TransactionType::Income,
            'amount' => 1200.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(15),
            'comment' => 'Freelance Web Design project',
        ]);
        $freelanceTx->tags()->attach($tagWork);

        $youtubeTx = $user->transactions()->create([
            'account_id' => $paypal->id,
            'category_id' => $youtubeCategory->id,
            'type' => TransactionType::Income,
            'amount' => 350.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(10),
            'comment' => 'AdSense payout',
        ]);

        // 8. Seed Budgets & Corresponding Expenses
        // Exceeded budget (Category: Bar, Amount: 100 EUR, Spent: 150 EUR)
        $user->budgets()->create([
            'category_id' => $categories['Bar']->id,
            'period' => BudgetPeriod::Monthly,
            'amount' => 100.00,
            'currency_code' => 'EUR',
        ]);

        $bar1 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Bar']->id,
            'type' => TransactionType::Expense,
            'amount' => 50.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(5),
            'comment' => 'Friday drinks with team',
        ]);
        $bar1->tags()->attach($tagLeisure);

        $bar2 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Bar']->id,
            'type' => TransactionType::Expense,
            'amount' => 60.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(3),
            'comment' => 'Cocktail bar',
        ]);
        $bar2->tags()->attach($tagLeisure);

        $bar3 = $user->transactions()->create([
            'account_id' => $cash->id,
            'category_id' => $categories['Bar']->id,
            'type' => TransactionType::Expense,
            'amount' => 40.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(1),
            'comment' => 'Coffee & beers',
        ]);

        // Close to exceeded budget (Category: Cosmetics, Amount: 150 EUR, Spent: 140 EUR)
        $user->budgets()->create([
            'category_id' => $categories['Cosmetics']->id,
            'period' => BudgetPeriod::Monthly,
            'amount' => 150.00,
            'currency_code' => 'EUR',
        ]);

        $cosmetics1 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Cosmetics']->id,
            'type' => TransactionType::Expense,
            'amount' => 75.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(7),
            'comment' => 'Perfume store',
        ]);

        $cosmetics2 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Cosmetics']->id,
            'type' => TransactionType::Expense,
            'amount' => 65.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(4),
            'comment' => 'Skincare essentials',
        ]);

        // Start of spending budget (Category: Travel, Amount: 1000 EUR, Spent: 50 EUR)
        $user->budgets()->create([
            'category_id' => $categories['Travel']->id,
            'period' => BudgetPeriod::Monthly,
            'amount' => 1000.00,
            'currency_code' => 'EUR',
        ]);

        $travel1 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Travel']->id,
            'type' => TransactionType::Expense,
            'amount' => 50.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(12),
            'comment' => 'Train ticket booking deposit',
        ]);
        $travel1->tags()->attach($tagLeisure);

        // 9. Other general expense transactions
        $food1 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Food']->id,
            'type' => TransactionType::Expense,
            'amount' => 30.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(8),
            'comment' => 'Grocery store',
        ]);

        $food2 = $user->transactions()->create([
            'account_id' => $cash->id,
            'category_id' => $categories['Food']->id,
            'type' => TransactionType::Expense,
            'amount' => 15.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(6),
            'comment' => 'Local bakery',
        ]);

        $bus1 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Transport']->id,
            'type' => TransactionType::Expense,
            'amount' => 2.50,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(9),
            'comment' => 'Bus fare',
        ]);

        $bus2 = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Transport']->id,
            'type' => TransactionType::Expense,
            'amount' => 2.50,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(8),
            'comment' => 'Bus fare returning',
        ]);

        $train = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Transport']->id,
            'type' => TransactionType::Expense,
            'amount' => 25.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(7),
            'comment' => 'Intercity train ticket',
        ]);

        $shop = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $categories['Shopping']->id,
            'type' => TransactionType::Expense,
            'amount' => 120.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(11),
            'comment' => 'Autumn jacket',
        ]);

        $gym = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $gymCategory->id,
            'type' => TransactionType::Expense,
            'amount' => 50.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(18),
            'comment' => 'Monthly gym membership',
        ]);
        $gym->tags()->attach($tagHealth);

        $gadget = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => $gadgetsCategory->id,
            'type' => TransactionType::Expense,
            'amount' => 800.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(14),
            'comment' => 'IPS Monitor for work',
        ]);
        $gadget->tags()->attach($tagImportant);

        // 10. Seed Transfers (linked outgoing/incoming transactions)
        // Transfer 1: ATM Withdrawal (Bank Card -> Cash Wallet, 500 EUR)
        $t1_uuid = Str::uuid()->toString();
        $t1_out = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => 500.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(17),
            'comment' => 'ATM Withdrawal',
            'transfer_id' => $t1_uuid,
        ]);
        $t1_in = $user->transactions()->create([
            'account_id' => $cash->id,
            'category_id' => null,
            'type' => TransactionType::Income,
            'amount' => 500.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(17),
            'comment' => 'ATM Withdrawal',
            'transfer_id' => $t1_uuid,
            'related_transaction_id' => $t1_out->id,
        ]);
        $t1_out->update(['related_transaction_id' => $t1_in->id]);

        // Transfer 2: Bank Card -> Crypto Wallet (200 EUR)
        $t2_uuid = Str::uuid()->toString();
        $t2_out = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => 200.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(13),
            'comment' => 'Buy BTC on exchange',
            'transfer_id' => $t2_uuid,
        ]);
        $t2_in = $user->transactions()->create([
            'account_id' => $crypto->id,
            'category_id' => null,
            'type' => TransactionType::Income,
            'amount' => 200.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(13),
            'comment' => 'Buy BTC on exchange',
            'transfer_id' => $t2_uuid,
            'related_transaction_id' => $t2_out->id,
        ]);
        $t2_out->update(['related_transaction_id' => $t2_in->id]);

        // Transfer 3: PayPal -> Bank Card (150 EUR)
        $t3_uuid = Str::uuid()->toString();
        $t3_out = $user->transactions()->create([
            'account_id' => $paypal->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => 150.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(2),
            'comment' => 'Withdraw PayPal funds to Card',
            'transfer_id' => $t3_uuid,
        ]);
        $t3_in = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => null,
            'type' => TransactionType::Income,
            'amount' => 150.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(2),
            'comment' => 'Withdraw PayPal funds to Card',
            'transfer_id' => $t3_uuid,
            'related_transaction_id' => $t3_out->id,
        ]);
        $t3_out->update(['related_transaction_id' => $t3_in->id]);

        // 11. Goals & Goal Deposits
        // Goal 1: New Laptop (Target 1500 EUR, Current 600 EUR)
        $laptopGoal = $user->goals()->create([
            'name' => 'New Laptop',
            'target_amount' => 1500.00,
            'current_amount' => 600.00,
            'currency_code' => 'EUR',
            'status' => GoalStatus::Active,
            'deadline' => Carbon::now()->addMonths(3),
        ]);

        $dep1_tx = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => 300.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(10),
            'comment' => 'Laptop goal saving - Deposit 1',
        ]);
        GoalDeposit::create([
            'goal_id' => $laptopGoal->id,
            'transaction_id' => $dep1_tx->id,
            'amount' => 300.00,
            'comment' => 'Initial deposit',
        ]);

        $dep2_tx = $user->transactions()->create([
            'account_id' => $card->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => 300.00,
            'currency_code' => 'EUR',
            'date' => Carbon::now()->subDays(5),
            'comment' => 'Laptop goal saving - Deposit 2',
        ]);
        GoalDeposit::create([
            'goal_id' => $laptopGoal->id,
            'transaction_id' => $dep2_tx->id,
            'amount' => 300.00,
            'comment' => 'Second deposit',
        ]);

        // Goal 2: Summer Vacation (Target 3000 EUR, Current 2500 EUR)
        $vacationGoal = $user->goals()->create([
            'name' => 'Summer Vacation',
            'target_amount' => 3000.00,
            'current_amount' => 2500.00,
            'currency_code' => 'EUR',
            'status' => GoalStatus::Active,
            'deadline' => Carbon::now()->addMonths(6),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $dep_tx = $user->transactions()->create([
                'account_id' => $card->id,
                'category_id' => null,
                'type' => TransactionType::Expense,
                'amount' => 500.00,
                'currency_code' => 'EUR',
                'date' => Carbon::now()->subDays(15 - $i),
                'comment' => "Vacation saving - Deposit {$i}",
            ]);
            GoalDeposit::create([
                'goal_id' => $vacationGoal->id,
                'transaction_id' => $dep_tx->id,
                'amount' => 500.00,
                'comment' => "Saving installment {$i}",
            ]);
        }

        // 12. Calculate and Update Account Balances programmatically
        foreach ($user->accounts as $account) {
            $incomes = Transaction::where('account_id', $account->id)
                ->where('type', TransactionType::Income)
                ->sum('amount');

            $expenses = Transaction::where('account_id', $account->id)
                ->where('type', TransactionType::Expense)
                ->sum('amount');

            $account->update(['balance' => $incomes - $expenses]);
        }
    }
}
