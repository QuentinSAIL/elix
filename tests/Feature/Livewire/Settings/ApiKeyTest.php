<?php

use App\Livewire\Settings\ApiKey as ApiKeyComponent;
use App\Models\ApiKey;
use App\Models\ApiService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('api key component can be rendered', function () {
    $service = ApiService::factory()->create();

    Livewire::test(ApiKeyComponent::class)
        ->assertStatus(200)
        ->assertSee($service->name);
});

test('can mount with existing api keys', function () {
    $service = ApiService::factory()->create();
    $apiKey = ApiKey::factory()->for($this->user)->for($service)->create([
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);

    Livewire::test(ApiKeyComponent::class)
        ->assertSet('secret_ids.'.$service->id, 'test-secret-id')
        ->assertSet('secret_keys.'.$service->id, 'test-secret-key');
});

test('can mount without existing api keys', function () {
    $service = ApiService::factory()->create();

    Livewire::test(ApiKeyComponent::class)
        ->assertSet('secret_ids.'.$service->id, '')
        ->assertSet('secret_keys.'.$service->id, '');
});

test('can update api keys', function () {
    $service = ApiService::factory()->create();

    Livewire::test(ApiKeyComponent::class)
        ->set('secret_ids.'.$service->id, 'new-secret-id')
        ->set('secret_keys.'.$service->id, 'new-secret-key')
        ->call('updateApiKeys');

    $this->assertDatabaseHas('api_keys', [
        'user_id' => $this->user->id,
        'api_service_id' => $service->id,
        'secret_id' => 'new-secret-id',
        'secret_key' => 'new-secret-key',
    ]);
});

test('can update existing api keys', function () {
    $service = ApiService::factory()->create();
    $apiKey = ApiKey::factory()->for($this->user)->for($service)->create([
        'secret_id' => 'old-secret-id',
        'secret_key' => 'old-secret-key',
    ]);

    Livewire::test(ApiKeyComponent::class)
        ->set('secret_ids.'.$service->id, 'updated-secret-id')
        ->set('secret_keys.'.$service->id, 'updated-secret-key')
        ->call('updateApiKeys');

    $apiKey->refresh();
    $this->assertEquals('updated-secret-id', $apiKey->secret_id);
    $this->assertEquals('updated-secret-key', $apiKey->secret_key);
});

test('can delete api keys', function () {
    $service = ApiService::factory()->create();
    $apiKey = ApiKey::factory()->for($this->user)->for($service)->create();

    Livewire::test(ApiKeyComponent::class)
        ->call('deleteApiKeys', $service->id);

    $this->assertDatabaseMissing('api_keys', [
        'id' => $apiKey->id,
    ]);
});

test('handles gocardless validation failure', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'error' => 'Invalid credentials',
        ], 401),
    ]);

    $service = ApiService::factory()->create(['name' => 'GoCardless']);
    $apiKey = ApiKey::factory()->for($this->user)->for($service)->create([
        'secret_id' => 'old-secret-id',
        'secret_key' => 'old-secret-key',
    ]);

    Livewire::test(ApiKeyComponent::class)
        ->set('secret_ids.'.$service->id, 'new-secret-id')
        ->set('secret_keys.'.$service->id, 'new-secret-key')
        ->call('updateApiKeys');

    // Should not update the credentials
    $apiKey->refresh();
    $this->assertEquals('old-secret-id', $apiKey->secret_id);
    $this->assertEquals('old-secret-key', $apiKey->secret_key);
});

test('can test gocardless credentials', function () {
    Http::fake([
        'bankaccountdata.gocardless.com/api/v2/token/new/' => Http::response([
            'access' => 'test-access-token',
        ], 200),
    ]);

    $service = ApiService::factory()->create(['name' => 'GoCardless']);

    Livewire::test(ApiKeyComponent::class)
        ->set('secret_ids.'.$service->id, 'test-secret-id')
        ->set('secret_keys.'.$service->id, 'test-secret-key')
        ->call('updateApiKeys')
        ->assertDispatched('show-toast', function (string $eventName, array $params) {
            return $params['type'] === 'success' && str_contains($params['message'], 'API Keys updated successfully!');
        });

    $this->assertDatabaseHas('api_keys', [
        'user_id' => $this->user->id,
        'api_service_id' => $service->id,
        'secret_id' => 'test-secret-id',
        'secret_key' => 'test-secret-key',
    ]);
});
