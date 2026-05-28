<?php

use App\Services\Platform\PlatformServiceClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('services.platform_service.base_url', 'http://127.0.0.1:8011');
});

test('platform service client surfaces validation errors from backend', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/users/7' => Http::response([
            'message' => 'The given data was invalid.',
            'errors' => [
                'roles.0' => ['Role Super Admin tidak ditemukan.'],
            ],
        ], 422),
    ]);

    $client = app(PlatformServiceClient::class);

    expect(fn () => $client->updateUser('access-token-123', 7, [
        'name' => 'Supply Owner',
        'email' => 'supply.owner@example.test',
        'roles' => ['Super Admin'],
    ]))->toThrow(RuntimeException::class, 'The given data was invalid.');
});

test('platform service client falls back to nested backend errors when message is missing', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/users/7' => Http::response([
            'errors' => [
                'roles.0' => ['Role Super Admin tidak ditemukan.'],
            ],
        ], 422),
    ]);

    $client = app(PlatformServiceClient::class);

    expect(fn () => $client->updateUser('access-token-123', 7, [
        'name' => 'Supply Owner',
        'email' => 'supply.owner@example.test',
        'roles' => ['Super Admin'],
    ]))->toThrow(RuntimeException::class, 'Role Super Admin tidak ditemukan.');
});
