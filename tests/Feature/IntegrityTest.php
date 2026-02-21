<?php

use App\Jobs\VerifyAssetIntegrity;
use App\Models\Asset;
use App\Models\User;
use App\Services\S3Service;
use Illuminate\Support\Facades\Queue;

test('missing scope returns only assets with s3_missing_at set', function () {
    Asset::factory()->create(['s3_missing_at' => null]);
    $missing = Asset::factory()->create(['s3_missing_at' => now()]);

    $results = Asset::missing()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($missing->id);
});

test('is_missing accessor returns true when s3_missing_at is set', function () {
    $asset = Asset::factory()->create(['s3_missing_at' => now()]);

    expect($asset->is_missing)->toBeTrue();
});

test('is_missing accessor returns false when s3_missing_at is null', function () {
    $asset = Asset::factory()->create(['s3_missing_at' => null]);

    expect($asset->is_missing)->toBeFalse();
});

test('assets index with missing=1 filter shows only missing assets', function () {
    $user = User::factory()->create();
    Asset::factory()->create(['filename' => 'present.jpg', 's3_missing_at' => null]);
    Asset::factory()->create(['filename' => 'gone.jpg', 's3_missing_at' => now()]);

    $response = $this->actingAs($user)->get(route('assets.index', ['missing' => 1]));

    $response->assertStatus(200);
    $response->assertSee('gone.jpg');
    $response->assertDontSee('present.jpg');
});

test('verify integrity endpoint requires admin', function () {
    $user = User::factory()->create(['role' => 'editor']);

    $response = $this->actingAs($user)->postJson(route('system.verify-integrity'));

    $response->assertStatus(403);
});

test('verify integrity endpoint dispatches jobs for admin', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    Asset::factory()->count(3)->create();

    $response = $this->actingAs($admin)->postJson(route('system.verify-integrity'));

    $response->assertStatus(200);
    $response->assertJson(['success' => true, 'count' => 3]);
    Queue::assertPushed(VerifyAssetIntegrity::class, 3);
});

test('verify asset integrity job sets s3_missing_at when object is missing', function () {
    $asset = Asset::factory()->create(['s3_missing_at' => null]);

    $s3Service = Mockery::mock(S3Service::class);
    $s3Service->shouldReceive('getObjectMetadata')
        ->with($asset->s3_key)
        ->andReturn(null);

    app()->instance(S3Service::class, $s3Service);

    $job = new VerifyAssetIntegrity($asset->id);
    $job->handle($s3Service);

    $asset->refresh();
    expect($asset->s3_missing_at)->not->toBeNull();
});

test('verify asset integrity job clears s3_missing_at when object exists', function () {
    $asset = Asset::factory()->create(['s3_missing_at' => now()]);

    $s3Service = Mockery::mock(S3Service::class);
    $s3Service->shouldReceive('getObjectMetadata')
        ->with($asset->s3_key)
        ->andReturn(['size' => 1024, 'mime_type' => 'image/jpeg', 'last_modified' => now(), 'etag' => 'abc']);

    app()->instance(S3Service::class, $s3Service);

    $job = new VerifyAssetIntegrity($asset->id);
    $job->handle($s3Service);

    $asset->refresh();
    expect($asset->s3_missing_at)->toBeNull();
});

test('verify asset integrity job preserves original s3_missing_at timestamp', function () {
    $originalTime = now()->subDays(3);
    $asset = Asset::factory()->create(['s3_missing_at' => $originalTime]);

    $s3Service = Mockery::mock(S3Service::class);
    $s3Service->shouldReceive('getObjectMetadata')
        ->with($asset->s3_key)
        ->andReturn(null);

    app()->instance(S3Service::class, $s3Service);

    $job = new VerifyAssetIntegrity($asset->id);
    $job->handle($s3Service);

    $asset->refresh();
    expect($asset->s3_missing_at->format('Y-m-d H:i:s'))->toBe($originalTime->format('Y-m-d H:i:s'));
});

test('integrity status endpoint requires admin', function () {
    $user = User::factory()->create(['role' => 'editor']);

    $response = $this->actingAs($user)->getJson(route('system.integrity-status'));

    $response->assertStatus(403);
});

test('integrity status endpoint returns correct counts for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Asset::factory()->count(3)->create(['s3_missing_at' => null]);
    Asset::factory()->count(2)->create(['s3_missing_at' => now()]);

    $response = $this->actingAs($admin)->getJson(route('system.integrity-status'));

    $response->assertStatus(200);
    $response->assertJson([
        'missing' => 2,
        'total' => 5,
    ]);
});
