<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_position_belongs_to_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertInstanceOf(Wallet::class, $position->wallet);
        $this->assertEquals($wallet->id, $position->wallet->id);
    }

    public function test_wallet_position_can_be_created(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'name' => 'Apple Inc',
            'ticker' => 'AAPL',
            'unit' => 'SHARE',
            'quantity' => '10.5',
            'price' => '150.25',
        ]);

        $this->assertDatabaseHas('wallet_positions', [
            'id' => $position->id,
            'wallet_id' => $wallet->id,
            'name' => 'Apple Inc',
            'ticker' => 'AAPL',
        ]);
    }

    public function test_wallet_position_has_correct_casts(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'quantity' => '10.5',
            'price' => '150.25',
        ]);

        $this->assertIsString($position->quantity);
        $this->assertIsString($position->price);
        $this->assertEquals('10.500000000000000000', $position->quantity);
        $this->assertEquals('150.250000000000000000', $position->price);
    }

    public function test_update_current_price_with_ticker(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
            'price' => '100',
        ]);

        // Mock the PriceService
        $priceService = $this->mock(PriceService::class);
        $priceService->shouldReceive('getPrice')
            ->with('AAPL', $wallet->unit)
            ->once()
            ->andReturn(150.0);

        $this->app->instance(PriceService::class, $priceService);

        $result = $position->updateCurrentPrice();

        $this->assertTrue($result);
        $position->refresh();
        $this->assertEquals('150.000000000000000000', $position->price);
    }

    public function test_update_current_price_without_ticker(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => null,
            'price' => '100',
        ]);

        $result = $position->updateCurrentPrice();

        $this->assertFalse($result);
        $position->refresh();
        $this->assertEquals('100.000000000000000000', $position->price);
    }

    public function test_update_current_price_when_price_service_returns_null(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'INVALID',
            'price' => '100',
        ]);

        // Mock the PriceService to return null
        $priceService = $this->mock(PriceService::class);
        $priceService->shouldReceive('getPrice')
            ->with('INVALID', $wallet->unit)
            ->once()
            ->andReturn(null);

        $this->app->instance(PriceService::class, $priceService);

        $result = $position->updateCurrentPrice();

        $this->assertFalse($result);
        $position->refresh();
        $this->assertEquals('100.000000000000000000', $position->price);
    }

    public function test_get_current_price_with_ticker(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'AAPL',
            'price' => '100',
        ]);

        // Mock the PriceService
        $priceService = $this->mock(PriceService::class);
        $priceService->shouldReceive('getPrice')
            ->with('AAPL', $wallet->unit)
            ->once()
            ->andReturn(150.0);

        $this->app->instance(PriceService::class, $priceService);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(150.0, $currentPrice);
        $position->refresh();
        $this->assertEquals('150.000000000000000000', $position->price);
    }

    public function test_get_current_price_without_ticker(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => null,
            'price' => '100',
        ]);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(100.0, $currentPrice);
    }

    public function test_get_current_price_when_price_service_returns_null(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'ticker' => 'INVALID',
            'price' => '100',
        ]);

        // Mock the PriceService to return null
        $priceService = $this->mock(PriceService::class);
        $priceService->shouldReceive('getPrice')
            ->with('INVALID', $wallet->unit)
            ->once()
            ->andReturn(null);

        $this->app->instance(PriceService::class, $priceService);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(100.0, $currentPrice);
    }

    public function test_get_value(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'quantity' => '10.5',
            'price' => '150.25',
        ]);

        $value = $position->getValue();

        $this->assertEquals(1577.625, $value);
    }

    public function test_get_formatted_value(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'quantity' => '10',
            'price' => '150.50',
        ]);

        $formattedValue = $position->getFormattedValue();

        $this->assertEquals('1505', $formattedValue);
    }

    public function test_get_formatted_value_removes_trailing_zeros(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create([
            'wallet_id' => $wallet->id,
            'quantity' => '10',
            'price' => '150.00',
        ]);

        $formattedValue = $position->getFormattedValue();

        $this->assertEquals('15', $formattedValue);
    }

    public function test_wallet_position_uses_uuid(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        $position = WalletPosition::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertIsString($position->id);
        $this->assertGreaterThan(20, strlen($position->id)); // UUIDs are longer than 20 chars
    }
}
