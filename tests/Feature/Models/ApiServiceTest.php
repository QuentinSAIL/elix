<?php

use App\Models\ApiKey;
use App\Models\ApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api service can be created', function () {
    $apiService = ApiService::factory()->create([
        'name' => 'Test Service',
        'description' => 'A test API service',
        'url' => 'http://test.com',
        'icon' => 'test-icon.png',
    ]);

    $this->assertDatabaseHas('api_services', [
        'name' => 'Test Service',
        'description' => 'A test API service',
        'url' => 'http://test.com',
        'icon' => 'test-icon.png',
    ]);
});

test('api service has many api keys', function () {
    $apiService = ApiService::factory()->create();
    ApiKey::factory()->count(3)->create(['api_service_id' => $apiService->id]);

    $this->assertCount(3, $apiService->apiKeys);
    $this->assertTrue($apiService->apiKeys->first() instanceof ApiKey);
});
