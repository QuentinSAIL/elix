<?php

namespace Tests\Feature\Models;

use App\Models\PriceAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_asset_can_be_created(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $this->assertDatabaseHas('price_assets', [
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => '150.250000000000000000',
            'currency' => 'EUR',
        ]);
    }

    public function test_price_asset_has_correct_casts(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $this->assertIsString($priceAsset->price);
        $this->assertInstanceOf(\Carbon\Carbon::class, $priceAsset->last_updated);
    }

    public function test_find_or_create_creates_new_asset(): void
    {
        $priceAsset = PriceAsset::findOrCreate('AAPL', 'STOCK');

        $this->assertDatabaseHas('price_assets', [
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'currency' => 'EUR',
        ]);
        $this->assertNull($priceAsset->last_updated);
    }

    public function test_find_or_create_returns_existing_asset(): void
    {
        $existing = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $found = PriceAsset::findOrCreate('AAPL', 'STOCK');

        $this->assertEquals($existing->id, $found->id);
        $this->assertEquals(1, PriceAsset::where('ticker', 'AAPL')->count());
    }

    public function test_update_price_updates_price_and_timestamp(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 100.0,
            'currency' => 'EUR',
            'last_updated' => now()->subHour(),
        ]);

        $result = $priceAsset->updatePrice(150.25, 'USD');

        $this->assertTrue($result);
        $priceAsset->refresh();
        $this->assertEquals('150.250000000000000000', $priceAsset->price);
        $this->assertEquals('USD', $priceAsset->currency);
        $this->assertNotNull($priceAsset->last_updated);
    }

    public function test_is_price_recent_returns_false_when_no_last_updated(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => null,
        ]);

        $this->assertFalse($priceAsset->isPriceRecent());
    }

    public function test_is_price_recent_returns_true_when_recent(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(6), // Within 12 hours
        ]);

        $this->assertTrue($priceAsset->isPriceRecent());
    }

    public function test_is_price_recent_returns_false_when_old(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(13), // Older than 12 hours
        ]);

        $this->assertFalse($priceAsset->isPriceRecent());
    }

    public function test_get_current_price_returns_price(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $this->assertEquals(150.25, $priceAsset->getCurrentPrice());
    }

    public function test_get_current_price_returns_null_when_no_price(): void
    {
        $priceAsset = PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => null,
            'currency' => 'EUR',
            'last_updated' => now(),
        ]);

        $this->assertNull($priceAsset->getCurrentPrice());
    }

    public function test_scope_with_recent_price(): void
    {
        // Create assets with recent prices
        PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(6),
        ]);

        PriceAsset::create([
            'ticker' => 'GOOGL',
            'type' => 'STOCK',
            'price' => 2500.0,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(1),
        ]);

        // Create asset with old price
        PriceAsset::create([
            'ticker' => 'MSFT',
            'type' => 'STOCK',
            'price' => 300.0,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(13),
        ]);

        // Create asset with no price
        PriceAsset::create([
            'ticker' => 'TSLA',
            'type' => 'STOCK',
            'price' => null,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(1),
        ]);

        $recentAssets = PriceAsset::withRecentPrice()->get();

        $this->assertCount(2, $recentAssets);
        $this->assertTrue($recentAssets->contains('ticker', 'AAPL'));
        $this->assertTrue($recentAssets->contains('ticker', 'GOOGL'));
        $this->assertFalse($recentAssets->contains('ticker', 'MSFT'));
        $this->assertFalse($recentAssets->contains('ticker', 'TSLA'));
    }

    public function test_scope_needs_update(): void
    {
        // Create asset that needs update (no last_updated)
        PriceAsset::create([
            'ticker' => 'AAPL',
            'type' => 'STOCK',
            'price' => 150.25,
            'currency' => 'EUR',
            'last_updated' => null,
        ]);

        // Create asset that needs update (old last_updated)
        PriceAsset::create([
            'ticker' => 'GOOGL',
            'type' => 'STOCK',
            'price' => 2500.0,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(13),
        ]);

        // Create asset that doesn't need update
        PriceAsset::create([
            'ticker' => 'MSFT',
            'type' => 'STOCK',
            'price' => 300.0,
            'currency' => 'EUR',
            'last_updated' => now()->subHours(6),
        ]);

        $needsUpdate = PriceAsset::needsUpdate()->get();

        $this->assertCount(2, $needsUpdate);
        $this->assertTrue($needsUpdate->contains('ticker', 'AAPL'));
        $this->assertTrue($needsUpdate->contains('ticker', 'GOOGL'));
        $this->assertFalse($needsUpdate->contains('ticker', 'MSFT'));
    }

    public function test_constants_are_defined(): void
    {
        $this->assertIsArray(PriceAsset::TYPES);
        $this->assertArrayHasKey('CRYPTO', PriceAsset::TYPES);
        $this->assertArrayHasKey('STOCK', PriceAsset::TYPES);
        $this->assertArrayHasKey('COMMODITY', PriceAsset::TYPES);
        $this->assertArrayHasKey('ETF', PriceAsset::TYPES);
        $this->assertArrayHasKey('BOND', PriceAsset::TYPES);
        $this->assertArrayHasKey('OTHER', PriceAsset::TYPES);
    }
}
