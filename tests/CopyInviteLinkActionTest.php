<?php

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tapp\FilamentInvite\Actions\CopyInviteLinkAction;
use Tapp\FilamentInvite\Concerns\InvitesUsers;
use Tapp\FilamentInvite\Notifications\SetPassword;
use Tapp\FilamentInvite\Support\InviteUrl;

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

it('generates an invite link for an eligible user without sending a notification', function (): void {
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

    $action = new class
    {
        use InvitesUsers;

        public function exposeCreateInviteLink(Model $user): string
        {
            return $this->createInviteLink($user);
        }
    };

    $url = $action->exposeCreateInviteLink($invitee);

    expect($url)
        ->toBeString()
        ->toContain('/reset-password/')
        ->toContain('email=' . urlencode($invitee->email))
        ->toContain('invite=1');

    Notification::assertNothingSent();
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
        ->toBe($invitee->getResetPasswordUrl($token));
});

it('rejects creating an invite link for ineligible users', function (): void {
    $inviter = InviteTestUser::query()->create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $verifiedUser = InviteTestUser::query()->create([
        'name' => 'Verified',
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'password' => 'secret',
    ]);

    $this->actingAs($inviter);

    $action = new class
    {
        use InvitesUsers;

        public function exposeCreateInviteLink(Model $user): string
        {
            return $this->createInviteLink($user);
        }
    };

    $action->exposeCreateInviteLink($verifiedUser);
})->throws(RuntimeException::class, 'User cannot be invited.');

it('creates a password broker token that can be used to set a password', function (): void {
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

    $action = new class
    {
        use InvitesUsers;

        public function exposeCreateInviteToken(Model $user): string
        {
            return $this->createInviteToken($user);
        }

        public function exposeCreateInviteLink(Model $user): string
        {
            return $this->createInviteLink($user);
        }
    };

    $token = $action->exposeCreateInviteToken($invitee);
    $url = InviteUrl::for($invitee, $token);

    expect($url)->toContain($token)
        ->and(Password::broker()->tokenExists($invitee, $token))->toBeTrue();

    // Creating via the link helper also yields a broker-valid token embedded in the URL.
    $linkUrl = $action->exposeCreateInviteLink($invitee);
    $linkToken = basename(parse_url($linkUrl, PHP_URL_PATH));

    expect(Password::broker()->tokenExists($invitee, $linkToken))->toBeTrue();

    Notification::assertNothingSent();
});

class InviteTestUser extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    use CanResetPassword;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => now()])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        //
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function can($abilities, $arguments = []): bool
    {
        return $abilities === 'update';
    }
}

class InviteTestUserWithCustomResetUrl extends InviteTestUser
{
    public function getResetPasswordUrl(string $token, array $parameters = []): string
    {
        return URL::to('/custom-reset/' . $token . '?email=' . urlencode($this->email));
    }
}
