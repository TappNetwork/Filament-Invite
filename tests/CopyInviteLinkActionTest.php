<?php

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tapp\FilamentInvite\Actions\CopyInviteLinkAction;
use Tapp\FilamentInvite\Notifications\SetPassword;
use Tapp\FilamentInvite\Support\InviteUrl;
use Tapp\FilamentInvite\Tests\Fixtures\Models\InviteTestUser;
use Tapp\FilamentInvite\Tests\Fixtures\Models\InviteTestUserWithCustomResetUrl;
use Tapp\FilamentInvite\Tests\Fixtures\Pages\EditInviteTestUser;

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password')->nullable();
        $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table): void {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));

    config()->set('auth.defaults.passwords', 'users');

    config()->set('auth.passwords.users', [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ]);

    config()->set('auth.providers.users', [
        'driver' => 'eloquent',
        'model' => InviteTestUser::class,
    ]);

    config()->set('filament-invite.routes.reset', 'password.reset');

    Route::get('/reset-password/{token}', fn () => 'ok')->name('password.reset');

    app(PanelRegistry::class)->register(
        Panel::make()
            ->default()
            ->id('admin')
            ->path('admin')
    );
});

afterEach(function (): void {
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('users');

    $registry = app(PanelRegistry::class);
    $registry->panels = [];
    $registry->defaultPanel = null;
});

it('registers copy invite link action with the expected default name', function (): void {
    expect(CopyInviteLinkAction::getDefaultName())->toBe('copy_invite_link');
});

it('builds the same URL shape the SetPassword notification would send', function (): void {
    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $token = 'test-token-value';

    $mail = (new SetPassword($token))->toMail($invitee);

    expect($mail->actionUrl)->toBe(InviteUrl::for($invitee, $token));
});

it('uses getResetPasswordUrl when the user model defines it', function (): void {
    $invitee = InviteTestUserWithCustomResetUrl::query()->create([
        'name' => 'Invitee',
        'email' => 'custom-url@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $token = 'custom-token';

    $mail = (new SetPassword($token))->toMail($invitee);

    expect($mail->actionUrl)
        ->toBe(InviteUrl::for($invitee, $token))
        ->toBe($invitee->getResetPasswordUrl($token, ['invite' => 1]));
});

it('hides the action for verified users', function (): void {
    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $verified = InviteTestUser::query()->create([
        'name' => 'Verified',
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $this->actingAs($inviter);

    Livewire::test(EditInviteTestUser::class, ['record' => $verified])
        ->assertActionHidden(CopyInviteLinkAction::class);
});

it('hides the action when the admin cannot update the user', function (): void {
    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);
    $inviter->canUpdateOthers = false;

    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $this->actingAs($inviter);

    Livewire::test(EditInviteTestUser::class, ['record' => $invitee])
        ->assertActionHidden(CopyInviteLinkAction::class);
});

it('shows the action for eligible users', function (): void {
    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $this->actingAs($inviter);

    Livewire::test(EditInviteTestUser::class, ['record' => $invitee])
        ->assertActionVisible(CopyInviteLinkAction::class);
});

it('does not mint a token when the modal is opened', function (): void {
    Notification::fake();

    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $this->actingAs($inviter);

    Livewire::test(EditInviteTestUser::class, ['record' => $invitee])
        ->mountAction(CopyInviteLinkAction::class)
        ->assertSchemaStateSet([
            'invite_url' => null,
        ]);

    expect(Password::broker()->getRepository()->recentlyCreatedToken($invitee))->toBeFalse();

    Notification::assertNothingSent();
});

it('generates a broker-valid invite URL without sending a notification', function (): void {
    Notification::fake();

    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $this->actingAs($inviter);

    $url = null;

    Livewire::test(EditInviteTestUser::class, ['record' => $invitee])
        ->mountAction(CopyInviteLinkAction::class)
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertSchemaStateSet(function (array $data) use (&$url): bool {
            $url = $data['invite_url'] ?? null;

            return is_string($url)
                && str_contains($url, '/reset-password/')
                && str_contains($url, 'email=' . urlencode('invitee@example.com'))
                && str_contains($url, 'invite=1');
        });

    $token = basename(parse_url($url, PHP_URL_PATH));

    expect(Password::broker()->tokenExists($invitee, $token))->toBeTrue();

    Notification::assertNothingSent();
});

it('invalidates the previous token when generating twice', function (): void {
    Notification::fake();

    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $invitee = InviteTestUser::query()->create([
        'name' => 'Invitee',
        'email' => 'invitee@example.com',
        'email_verified_at' => null,
        'password' => null,
    ]);

    $this->actingAs($inviter);

    $firstUrl = null;
    $secondUrl = null;

    Livewire::test(EditInviteTestUser::class, ['record' => $invitee])
        ->mountAction(CopyInviteLinkAction::class)
        ->callMountedAction()
        ->assertSchemaStateSet(function (array $data) use (&$firstUrl): bool {
            $firstUrl = $data['invite_url'] ?? null;

            return filled($firstUrl);
        })
        ->callMountedAction()
        ->assertSchemaStateSet(function (array $data) use (&$secondUrl, &$firstUrl): bool {
            $secondUrl = $data['invite_url'] ?? null;

            return filled($secondUrl) && $secondUrl !== $firstUrl;
        });

    $firstToken = basename(parse_url($firstUrl, PHP_URL_PATH));
    $secondToken = basename(parse_url($secondUrl, PHP_URL_PATH));

    expect(Password::broker()->tokenExists($invitee, $firstToken))->toBeFalse()
        ->and(Password::broker()->tokenExists($invitee, $secondToken))->toBeTrue();

    Notification::assertNothingSent();
});
