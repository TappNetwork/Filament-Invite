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
