# Invite users from Filament panel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tapp/filament-invite.svg?style=flat-square)](https://packagist.org/packages/tapp/filament-invite)
![GitHub Tests Action Status](https://github.com/TappNetwork/Filament-Invite/actions/workflows/run-tests.yml/badge.svg)
![GitHub Code Style Action Status](https://github.com/TappNetwork/Filament-Invite/actions/workflows/fix-php-code-style-issues.yml/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/tapp/filament-invite.svg?style=flat-square)](https://packagist.org/packages/tapp/filament-invite)

Provides an action to invite users from Filament users resource.

## Version Compatibility

 Filament | Filament Invite
:---------|:---------------
 3.x      | 1.x
 4.x      | 2.x

## Installation

You can install the package via Composer:

### For Filament 3

```bash
composer require tapp/filament-invite:"^1.0"
```

### For Filament 4

```bash
composer require tapp/filament-invite:"^2.0"
```

Please check the docs for [Filament 4 here](https://github.com/TappNetwork/Filament-Invite/tree/2.x)

You can publish the config using:

```bash
php artisan filament-invite:install
```

## Requirements

-   User model which implements password resets and email verification (Laravel defaults)

## Usage

Add invite action to a table

```php
public static function table(Table $table): Table
{
    return $table
        ->actions([
            \Tapp\FilamentInvite\Tables\InviteAction::make(),
        ]);
}
```

Invite action outside of a table uses a different class

```php
    protected function getHeaderActions(): array
    {
        return [
            \Tapp\FilamentInvite\Actions\InviteAction::make(),
        ];
    }

```

## Notification Routing

The password reset URL can be customized or use the Filament's authentication system:

- **Default**: Uses Filament's built-in routing (respects panel's `passwordResetRouteSlug` configuration)
- **Custom**: Uses completely custom routes outside Filament's authentication system

```php
// Get the appropriate panel
$panel = $this->filamentPanelId
    ? Filament::getPanel($this->filamentPanelId)
    : Filament::getDefaultPanel();

if (empty(config('filament-invite.routes.reset'))) {
    // Use Filament's built-in routing (respects panel's passwordResetRouteSlug configuration)
    $url = $panel->getResetPasswordUrl($this->token, $notifiable, ['invite' => true]);
} else {
    // Support both custom routes and full URLs for maximum flexibility
    $customRoute = config('filament-invite.routes.reset');

    // Check if it's a full URL (starts with http/https)
    if (str_starts_with($customRoute, 'http')) {
        // Use the full URL directly
        $url = $customRoute . '?' . http_build_query([
            'token' => $this->token,
            'email' => $email,
            'invite' => true,
        ]);
    } else {
        // Use as route name - determine base URL based on configuration
        $routeUrl = route($customRoute, [
            'token' => $this->token,
            'email' => $email,
            'invite' => true,
        ], false);

        // Choose base URL: panel URL or app.url (for backward compatibility)
        $usePanelUrl = config('filament-invite.use_panel_url', false);
        $baseUrl = $usePanelUrl ? $panel->getUrl() : config('app.url');

        $url = rtrim($baseUrl, '/') . '/' . ltrim($routeUrl, '/');
    }
}
```

### Integration with Filament Panel Configuration

When using the default routing, the package automatically respects your panel's authentication configuration:

```php
// In your panel configuration
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->passwordResetRouteSlug('custom-reset') // This will be used automatically
        ->passwordResetRequestRouteSlug('custom-request');
}
```

To use custom routing you have plenty of options:

### Option 1: Custom Route Name

```php
'routes' => [
    'reset' => 'your.custom.route.name',
],
```

### Option 2: Full URL (External System)

```php
'routes' => [
    'reset' => 'https://external-auth.example.com/reset-password',
],
```

### Option 3: Different Domain/Subdomain

```php
'routes' => [
    'reset' => 'https://auth.yourapp.com/password-reset',
],
```
The package automatically detects whether you're providing a route name or a full URL and handles the URL construction accordingly.

### URL Base Configuration

For custom routes, you can choose between using the panel's URL or the application's URL:

```php
// config/filament-invite.php
return [
    'routes' => [
        'reset' => 'your.custom.route.name',
    ],
    
    // URL Configuration
    'use_panel_url' => false, // Default: false (uses app.url for backward compatibility)
];
```

**Options:**
1. `'use_panel_url' => false` (default): Uses `config('app.url')` - maintains backward compatibility
2. `'use_panel_url' => true`: Uses `$panel->getUrl()`

## Customization

### Reset URL

implement getResetPasswordUrl on the user model

```php
public function getResetPasswordUrl(string $token, array $parameters = []): string
{
    return URL::signedRoute(
        'filament.admin.auth.password-reset.reset',
        [
            'email' => $this->email,
            'token' => $token,
            ...$parameters,
        ],
    );
}
```

### Notification

implement the sendPasswordSetNotification method on the user model

```php
public function sendPasswordSetNotification($token)
{
    Notification::send($this, new SetPassword($token));
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [TappNetwork](https://github.com/scottgrayson)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
