<?php

use App\Models\Asset;
use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('api assets index requires authentication', function () {
    $response = $this->getJson('/api/assets');

    $response->assertUnauthorized();
});

test('api assets index returns paginated assets', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Asset::factory()->count(5)->create();

    $response = $this->getJson('/api/assets');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'filename', 'mime_type', 'size'],
        ],
    ]);
});

test('api assets index can filter by search', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Asset::factory()->create(['filename' => 'findme.jpg']);
    Asset::factory()->create(['filename' => 'other.pdf']);

    $response = $this->getJson('/api/assets?search=findme');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['filename' => 'findme.jpg']);
});

test('api assets index can filter by type', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Asset::factory()->image()->create();
    Asset::factory()->pdf()->create();

    $response = $this->getJson('/api/assets?type=image');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('api can get single asset', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $asset = Asset::factory()->create(['filename' => 'test.jpg']);

    $response = $this->getJson("/api/assets/{$asset->id}");

    $response->assertOk();
    $response->assertJsonFragment(['filename' => 'test.jpg']);
});

test('api can update asset', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $asset = Asset::factory()->create(['user_id' => $user->id]);

    $response = $this->patchJson("/api/assets/{$asset->id}", [
        'alt_text' => 'Updated alt text',
        'caption' => 'Updated caption',
    ]);

    $response->assertOk();

    $asset->refresh();
    expect($asset->alt_text)->toBe('Updated alt text');
    expect($asset->caption)->toBe('Updated caption');
});

test('api can delete asset', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $asset = Asset::factory()->create(['user_id' => $user->id]);
    $assetId = $asset->id;

    $response = $this->deleteJson("/api/assets/{$assetId}");

    $response->assertOk();
    $this->assertSoftDeleted('assets', ['id' => $assetId]);
});

test('api tags index requires authentication', function () {
    $response = $this->getJson('/api/tags');

    $response->assertUnauthorized();
});

test('api tags index returns all tags', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Tag::factory()->count(3)->create();

    $response = $this->getJson('/api/tags');

    $response->assertOk();
    $response->assertJsonCount(3);
});

test('api tags index can filter by type', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Tag::factory()->user()->count(2)->create();
    Tag::factory()->ai()->count(3)->create();

    $response = $this->getJson('/api/tags?type=user');

    $response->assertOk();
    $response->assertJsonCount(2);
});

test('api asset meta endpoint is public', function () {
    $asset = Asset::factory()->create([
        'alt_text' => 'Test alt text',
        'caption' => 'Test caption',
    ]);

    // The meta endpoint should work without authentication
    $response = $this->getJson('/api/assets/meta?url='.urlencode($asset->url));

    $response->assertOk();
    $response->assertJsonFragment(['alt_text' => 'Test alt text']);
});

test('api asset meta returns error for unknown url', function () {
    $response = $this->getJson('/api/assets/meta?url='.urlencode('https://example.com/nonexistent.jpg'));

    $response->assertStatus(400);
});

test('api assets index can sort by date ascending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['updated_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['updated_at' => now()]);

    $response = $this->getJson('/api/assets?sort=date_asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($older->id);
    expect($data[1]['id'])->toBe($newer->id);
});

test('api assets index can sort by date descending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['updated_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['updated_at' => now()]);

    $response = $this->getJson('/api/assets?sort=date_desc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($newer->id);
    expect($data[1]['id'])->toBe($older->id);
});

test('api assets index can sort by upload date ascending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['created_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['created_at' => now()]);

    $response = $this->getJson('/api/assets?sort=upload_asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($older->id);
    expect($data[1]['id'])->toBe($newer->id);
});

test('api assets index can sort by upload date descending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['created_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['created_at' => now()]);

    $response = $this->getJson('/api/assets?sort=upload_desc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($newer->id);
    expect($data[1]['id'])->toBe($older->id);
});

test('api assets index can sort by size', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $small = Asset::factory()->create(['size' => 1000]);
    $large = Asset::factory()->create(['size' => 10000]);

    $response = $this->getJson('/api/assets?sort=size_asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($small->id);
    expect($data[1]['id'])->toBe($large->id);

    $response = $this->getJson('/api/assets?sort=size_desc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($large->id);
    expect($data[1]['id'])->toBe($small->id);
});

test('api assets index can sort by name', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $alpha = Asset::factory()->create(['filename' => 'alpha.jpg']);
    $zeta = Asset::factory()->create(['filename' => 'zeta.jpg']);

    $response = $this->getJson('/api/assets?sort=name_asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['filename'])->toBe('alpha.jpg');
    expect($data[1]['filename'])->toBe('zeta.jpg');

    $response = $this->getJson('/api/assets?sort=name_desc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['filename'])->toBe('zeta.jpg');
    expect($data[1]['filename'])->toBe('alpha.jpg');
});

test('api assets search can sort results', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['filename' => 'test-old.jpg', 'created_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['filename' => 'test-new.jpg', 'created_at' => now()]);

    $response = $this->getJson('/api/assets/search?q=test&sort=upload_asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($older->id);
    expect($data[1]['id'])->toBe($newer->id);
});

test('api assets index defaults to newest first when no sort specified', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $older = Asset::factory()->create(['updated_at' => now()->subDays(2)]);
    $newer = Asset::factory()->create(['updated_at' => now()]);

    $response = $this->getJson('/api/assets');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($newer->id);
    expect($data[1]['id'])->toBe($older->id);
});
