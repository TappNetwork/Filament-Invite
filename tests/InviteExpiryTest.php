<?php

use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tapp\FilamentInvite\FilamentInvitePlugin;
use Tapp\FilamentInvite\Http\InviteMiddleware;
use Tapp\FilamentInvite\Support\InviteUrl;
use Tapp\FilamentInvite\Tests\Fixtures\Models\InviteTestUser;
use Tapp\FilamentInvite\Tests\Fixtures\Models\InviteTestUserWithFilamentResetUrl;

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table): void {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    config()->set('app.url', 'http://localhost');

    config()->set('auth.defaults.passwords', 'users');
    config()->set('auth.defaults.guard', 'web');

    config()->set('auth.passwords.users', [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 0,
    ]);

    config()->set('auth.providers.users', [
        'driver' => 'eloquent',
        'model' => InviteTestUser::class,
    ]);

    config()->set('auth.guards.web', [
        'driver' => 'session',
        'provider' => 'users',
    ]);

    config()->set('filament-invite.expire', 60 * 24 * 2);
    config()->set('filament-invite.routes.reset', 'password.reset');

    Route::middleware('web')
        ->get('/reset-password/{token}', fn () => 'ok')
        ->name('password.reset');

    $panel = Filament::getPanel('admin');

    expect($panel->hasPlugin(FilamentInvitePlugin::ID))->toBeTrue()
        ->and($panel->getMiddleware())->toContain(InviteMiddleware::class);

    // Panel auth routes use the panel middleware stack (not the `web` group).
    Route::middleware([...$panel->getMiddleware(), 'signed'])
        ->get('/admin/password-reset/reset', ResetPassword::class)
        ->name('filament.admin.auth.password-reset.reset');

    Filament::setCurrentPanel('admin');
});

afterEach(function (): void {
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('users');
});

it('passes invite=1 into getResetPasswordUrl overrides', function (): void {
    $user = InviteTestUserWithFilamentResetUrl::query()->create([
        'name' => 'Invitee',
        'email' => 'filament-invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $url = InviteUrl::for($user, 'token-value');

    expect($url)
        ->toContain('/admin/password-reset/reset')
        ->toContain('invite=1');
});

it('allows a Filament panel invite reset after 61 minutes', function (): void {
    Event::fake([PasswordReset::class]);

    config()->set('auth.providers.users.model', InviteTestUserWithFilamentResetUrl::class);

    $user = InviteTestUserWithFilamentResetUrl::query()->create([
        'name' => 'Invitee',
        'email' => 'filament-invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $token = Password::broker()->createToken($user);
    $url = InviteUrl::for($user, $token);

    $route = Route::getRoutes()->getByName('filament.admin.auth.password-reset.reset');

    expect($route->gatherMiddleware())->toContain(InviteMiddleware::class)
        ->and($route->gatherMiddleware())->not->toContain('web');

    $this->get($url)->assertSuccessful();
    expect(session('invite'))->toBeTrue();

    $this->travel(61)->minutes();

    Livewire::test(ResetPassword::class, [
        'email' => $user->email,
        'token' => $token,
    ])
        ->fillForm([
            'password' => 'new-password-pass',
            'passwordConfirmation' => 'new-password-pass',
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    expect(Hash::check('new-password-pass', $user->fresh()->password))->toBeTrue();
});

it('allows a package-route invite reset after 61 minutes', function (): void {
    $user = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'package-invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $token = Password::broker()->createToken($user);
    $url = InviteUrl::for($user, $token);

    expect($url)->toContain('invite=1');

    $this->get($url)->assertSuccessful();
    expect(session('invite'))->toBeTrue()
        ->and(config('auth.passwords.users.expire'))->toBe(config('filament-invite.expire'));

    $this->travel(61)->minutes();

    $status = Password::broker()->reset(
        [
            'email' => $user->email,
            'password' => 'new-password-pass',
            'password_confirmation' => 'new-password-pass',
            'token' => $token,
        ],
        function (InviteTestUser $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        },
    );

    expect($status)->toBe(Password::PASSWORD_RESET)
        ->and(Hash::check('new-password-pass', $user->fresh()->password))->toBeTrue();
});

it('rejects a normal password reset after 61 minutes', function (): void {
    $user = InviteTestUser::query()->create([
        'name' => 'User',
        'email' => 'normal-reset@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]))->assertSuccessful();

    expect(session()->has('invite'))->toBeFalse()
        ->and(config('auth.passwords.users.expire'))->toBe(60);

    $this->travel(61)->minutes();

    $status = Password::broker()->reset(
        [
            'email' => $user->email,
            'password' => 'new-password-pass',
            'password_confirmation' => 'new-password-pass',
            'token' => $token,
        ],
        function (InviteTestUser $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        },
    );

    expect($status)->toBe(Password::INVALID_TOKEN)
        ->and(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
