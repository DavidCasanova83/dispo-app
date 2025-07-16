<?php

use App\Models\User;
use App\Models\UserColorSettings;

test('user can access color settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/colors');

    $response->assertStatus(200);
    $response->assertSee('Couleurs');
    $response->assertSee('Personnalisez les couleurs');
});

test('default colors are returned when no settings exist', function () {
    $defaults = UserColorSettings::getDefaultColors();

    expect($defaults['primary_color'])->toBe('#3A9C92');
    expect($defaults['secondary_color'])->toBe('#7AB6A8');
    expect($defaults['accent_color'])->toBe('#FFFDF4');
    expect($defaults['background_color'])->toBe('#FAF7F3');
});

test('color settings model works correctly', function () {
    $user = User::factory()->create();
    
    $settings = UserColorSettings::create([
        'user_id' => $user->id,
        'primary_color' => '#FF5733',
        'secondary_color' => '#33FF57',
        'accent_color' => '#3357FF',
        'background_color' => '#FFFFFF',
    ]);

    expect($settings->primary_color)->toBe('#FF5733');
    expect($settings->toCssVariables())->toHaveKey('--color-primary');
});

test('color settings are loaded in head for authenticated users', function () {
    $user = User::factory()->create();
    
    UserColorSettings::create([
        'user_id' => $user->id,
        'primary_color' => '#FF5733',
        'secondary_color' => '#33FF57',
        'accent_color' => '#3357FF',
        'background_color' => '#FFFFFF',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('--color-primary: #FF5733', false);
    $response->assertSee('--color-secondary: #33FF57', false);
});
