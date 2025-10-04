<?php

namespace Tests\Unit\Models;

use App\Models\Wallet;
use App\Models\WalletPosition;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * @coversDefaultClass \App\Models\WalletPosition
 */
class WalletPositionTest extends TestCase
{
    use RefreshDatabase;

    #[test]
    public function it_belongs_to_a_wallet()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create();

        $this->assertInstanceOf(Wallet::class, $position->wallet);
        $this->assertEquals($wallet->id, $position->wallet->id);
    }

    #[test]
    public function update_current_price_updates_price_when_ticker_exists()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => 'BTC', 'price' => 100.0]);

        $priceServiceMock = Mockery::mock(PriceService::class);
        $priceServiceMock->shouldReceive('getPrice')
            ->with('BTC', $wallet->unit)
            ->andReturn(200.0)
            ->once();
        $this->app->instance(PriceService::class, $priceServiceMock);

        $updated = $position->updateCurrentPrice();

        $this->assertTrue($updated);
        $this->assertEquals(200.0, $position->fresh()->price);
    }

    #[test]
    public function update_current_price_returns_false_when_no_ticker()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => null, 'price' => 100.0]);

        $updated = $position->updateCurrentPrice();

        $this->assertFalse($updated);
        $this->assertEquals(100.0, $position->fresh()->price);
    }

    #[test]
    public function update_current_price_returns_false_when_price_service_returns_null()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => 'UNKNOWN', 'price' => 100.0]);

        $priceServiceMock = Mockery::mock(PriceService::class);
        $priceServiceMock->shouldReceive('getPrice')
            ->with('UNKNOWN', $wallet->unit)
            ->andReturn(null)
            ->once();
        $this->app->instance(PriceService::class, $priceServiceMock);

        $updated = $position->updateCurrentPrice();

        $this->assertFalse($updated);
        $this->assertEquals(100.0, $position->fresh()->price);
    }

    #[test]
    public function get_current_price_returns_market_price_and_updates_stored_price_when_ticker_exists()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => 'BTC', 'price' => 100.0]);

        $priceServiceMock = Mockery::mock(PriceService::class);
        $priceServiceMock->shouldReceive('getPrice')
            ->with('BTC', $wallet->unit)
            ->andReturn(200.0)
            ->once();
        $this->app->instance(PriceService::class, $priceServiceMock);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(200.0, $currentPrice);
        $this->assertEquals(200.0, $position->fresh()->price);
    }

    #[test]
    public function get_current_price_returns_stored_price_when_no_ticker()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => null, 'price' => 100.0]);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(100.0, $currentPrice);
        $this->assertEquals(100.0, $position->fresh()->price);
    }

    #[test]
    public function get_current_price_returns_stored_price_when_price_service_returns_null()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['ticker' => 'UNKNOWN', 'price' => 100.0]);

        $priceServiceMock = Mockery::mock(PriceService::class);
        $priceServiceMock->shouldReceive('getPrice')
            ->with('UNKNOWN', $wallet->unit)
            ->andReturn(null)
            ->once();
        $this->app->instance(PriceService::class, $priceServiceMock);

        $currentPrice = $position->getCurrentPrice();

        $this->assertEquals(100.0, $currentPrice);
        $this->assertEquals(100.0, $position->fresh()->price);
    }

    #[test]
    public function get_value_calculates_correctly()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['quantity' => 2.5, 'price' => 50.0]);

        $this->assertEquals(125.0, $position->getValue());
    }

    #[test]
    public function get_formatted_value_returns_correct_string()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['quantity' => 2.5, 'price' => 50.0]);

        $this->assertEquals('125', $position->getFormattedValue());

        $position->update(['quantity' => 2.5, 'price' => 50.12345678]);
        $this->assertEquals('125.308642', $position->getFormattedValue());

        $position->update(['quantity' => 2.5, 'price' => 50.00000000]);
        $this->assertEquals('125', $position->getFormattedValue());
    }

    #[test]
    public function it_calculates_value_without_mocking_price_service()
    {
        $wallet = Wallet::factory()->create();
        $position = WalletPosition::factory()->for($wallet)->create(['quantity' => 3.0, 'price' => 25.0]);

        $this->assertEquals(75.0, $position->getValue());
    }
}