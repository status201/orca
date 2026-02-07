<?php

use App\Models\Setting;
use App\Models\User;

test('locale defaults to english', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    expect(app()->getLocale())->toBe('en');
});

test('global locale setting applies when user has no preference', function () {
    Setting::set('locale', 'nl', 'string', 'display');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    expect(app()->getLocale())->toBe('nl');
});

test('user locale preference overrides global setting', function () {
    Setting::set('locale', 'nl', 'string', 'display');
    $user = User::factory()->create([
        'preferences' => ['locale' => 'en'],
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    expect(app()->getLocale())->toBe('en');
});

test('html lang attribute reflects locale', function () {
    Setting::set('locale', 'nl', 'string', 'display');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('<html lang="nl"', false);
});

test('unsupported locale in user preference is ignored', function () {
    Setting::set('locale', 'nl', 'string', 'display');
    $user = User::factory()->create([
        'preferences' => ['locale' => 'xx'],
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    expect(app()->getLocale())->toBe('nl');
});

test('unsupported global locale falls back to config', function () {
    Setting::set('locale', 'xx', 'string', 'display');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    expect(app()->getLocale())->toBe('en');
});
