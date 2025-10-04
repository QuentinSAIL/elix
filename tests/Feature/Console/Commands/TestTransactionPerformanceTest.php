<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\TestTransactionPerformance;
use App\Models\BankTransactions;
use App\Models\User;
use App\Services\TransactionCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestTransactionPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $user = User::factory()->create();

        $this->artisan(TestTransactionPerformance::class)
            ->assertExitCode(0);
    }

    public function test_command_runs_with_specific_user(): void
    {
        $user = User::factory()->create();

        $this->artisan(TestTransactionPerformance::class, ['--user-id' => $user->id])
            ->assertExitCode(0);
    }

    public function test_command_handles_nonexistent_user(): void
    {
        $this->artisan(TestTransactionPerformance::class, ['--user-id' => '00000000-0000-0000-0000-000000000000'])
            ->assertExitCode(1);
    }

    public function test_command_handles_no_users(): void
    {
        $this->artisan(TestTransactionPerformance::class)
            ->assertExitCode(1);
    }

    public function test_basic_query_performance(): void
    {
        $user = User::factory()->create();

        // Create some transactions
        BankTransactions::factory()->count(10)->create();

        $command = new TestTransactionPerformance();

        // Mock the output to avoid null reference
        $command->setOutput($this->createMock(\Illuminate\Console\OutputStyle::class));

        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('testBasicQuery');
        $method->setAccessible(true);

        // This should not throw an exception
        $method->invoke($command, $user);

        $this->assertTrue(true);
    }

    public function test_eager_loading_performance(): void
    {
        $user = User::factory()->create();

        // Create some transactions with categories
        BankTransactions::factory()->count(5)->create();

        $command = new TestTransactionPerformance();

        // Mock the output to avoid null reference
        $command->setOutput($this->createMock(\Illuminate\Console\OutputStyle::class));

        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('testEagerLoading');
        $method->setAccessible(true);

        // This should not throw an exception
        $method->invoke($command, $user);

        $this->assertTrue(true);
    }

    public function test_cache_performance(): void
    {
        $user = User::factory()->create();

        // Create some transactions
        BankTransactions::factory()->count(3)->create();

        $command = new TestTransactionPerformance();

        // Mock the output to avoid null reference
        $command->setOutput($this->createMock(\Illuminate\Console\OutputStyle::class));

        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('testCachePerformance');
        $method->setAccessible(true);

        // Mock the cache service
        $cacheService = $this->mock(TransactionCacheService::class);
        $cacheService->shouldReceive('warmUpUserCache')->once();
        $cacheService->shouldReceive('getUserAccountCounts')->once()->andReturn([]);
        $cacheService->shouldReceive('getUserTotalCount')->once()->andReturn(0);
        $cacheService->shouldReceive('getCategories')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection([]));

        $this->app->instance(TransactionCacheService::class, $cacheService);

        // This should not throw an exception
        $method->invoke($command, $user);

        $this->assertTrue(true);
    }

    public function test_index_usage(): void
    {
        $user = User::factory()->create();

        // Create some transactions
        BankTransactions::factory()->count(5)->create([
            'description' => 'Test transaction',
        ]);

        $command = new TestTransactionPerformance();

        // Mock the output to avoid null reference
        $command->setOutput($this->createMock(\Illuminate\Console\OutputStyle::class));

        // Use reflection to test private method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('testIndexUsage');
        $method->setAccessible(true);

        // This should not throw an exception
        $method->invoke($command, $user);

        $this->assertTrue(true);
    }
}
