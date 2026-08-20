<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user cannot login with plain password mismatch when hash check is enforced', function () {
    $user = User::factory()->create([
        'email' => 'admin@tripwise.test',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $response = $this->post(route('login.post'), [
        'email' => 'admin@tripwise.test',
        'password' => 'WrongPassword',
    ]);

    $response->assertSessionHasErrors(['email']);
    expect(Auth::check())->toBeFalse();
});

test('user can authenticate securely with correct hashed credentials', function () {
    $user = User::factory()->create([
        'email' => 'finance@tripwise.test',
        'password' => Hash::make('SecurePassword456!'),
    ]);

    $response = $this->post(route('login.post'), [
        'email' => 'finance@tripwise.test',
        'password' => 'SecurePassword456!',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toEqual($user->id);
});

test('logout post route invalidates session and redirects to login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
    expect(Auth::check())->toBeFalse();
});
