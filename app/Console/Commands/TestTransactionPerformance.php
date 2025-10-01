<?php

namespace App\Console\Commands;

use App\Models\BankTransactions;
use App\Models\User;
use App\Services\TransactionCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestTransactionPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:test-performance {--user-id= : Test for specific user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test transaction page performance optimizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $this->error('No user found');
            return Command::FAILURE;
        }

        $this->info("Testing performance for user: {$user->name}");

        // Test 1: Query without optimizations
        $this->testBasicQuery($user);

        // Test 2: Query with eager loading
        $this->testEagerLoading($user);

        // Test 3: Query with cache
        $this->testCachePerformance($user);

        // Test 4: Index usage
        $this->testIndexUsage($user);

        return Command::SUCCESS;
    }

    private function testBasicQuery(User $user): void
    {
        $this->info("\n=== Test 1: Basic Query ===");

        $start = microtime(true);

        $transactions = $user->bankTransactions()
            ->orderBy('transaction_date', 'desc')
            ->take(50)
            ->get();

        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);

        $this->line("Time: {$time}ms");
        $this->line("Transactions loaded: " . $transactions->count());
        $this->line("Queries executed: " . count(DB::getQueryLog()));
    }

    private function testEagerLoading(User $user): void
    {
        $this->info("\n=== Test 2: Eager Loading ===");

        DB::flushQueryLog();
        $start = microtime(true);

        $transactions = $user->bankTransactions()
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'desc')
            ->take(50)
            ->get();

        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);

        $this->line("Time: {$time}ms");
        $this->line("Transactions loaded: " . $transactions->count());
        $this->line("Queries executed: " . count(DB::getQueryLog()));
    }

    private function testCachePerformance(User $user): void
    {
        $this->info("\n=== Test 3: Cache Performance ===");

        $cacheService = app(TransactionCacheService::class);

        // Warm up cache
        $start = microtime(true);
        $cacheService->warmUpUserCache($user);
        $end = microtime(true);
        $warmupTime = round(($end - $start) * 1000, 2);

        // Test cached queries
        $start = microtime(true);
        $counts = $cacheService->getUserAccountCounts($user);
        $total = $cacheService->getUserTotalCount($user);
        $categories = $cacheService->getCategories();
        $end = microtime(true);
        $cacheTime = round(($end - $start) * 1000, 2);

        $this->line("Cache warmup time: {$warmupTime}ms");
        $this->line("Cached queries time: {$cacheTime}ms");
        $this->line("Account counts: " . count($counts));
        $this->line("Total transactions: {$total}");
        $this->line("Categories cached: " . $categories->count());
    }

    private function testIndexUsage(User $user): void
    {
        $this->info("\n=== Test 4: Index Usage ===");

        DB::flushQueryLog();
        $start = microtime(true);

        // Test search query
        $transactions = $user->bankTransactions()
            ->whereRaw('LOWER(description) LIKE ?', ['%test%'])
            ->orderBy('transaction_date', 'desc')
            ->take(20)
            ->get();

        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);

        $this->line("Search query time: {$time}ms");
        $this->line("Results found: " . $transactions->count());

        // Show query plan
        $queryLog = DB::getQueryLog();
        if (!empty($queryLog)) {
            $this->line("Last query: " . $queryLog[count($queryLog) - 1]['query']);
        }
    }
}
