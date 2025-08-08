<?php

namespace Tapp\FilamentInvite\Notifications;

use Filament\Facades\Filament;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use function config;
use function route;
use function rtrim;
use function url;

class SetPassword extends Notification
{
    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct(public $token, public ?string $filamentPanelId = null) {}

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $email = '';

        if (is_string($notifiable)) {
            $email = $notifiable;
        }

        if (is_object($notifiable) && isset($notifiable->email)) {
            $email = $notifiable->email;
        }

        $message = (new MailMessage)
            ->subject(__('Account created'))
            ->line(__('An account for you has been created! Please set a password for your account!'));

        if (method_exists($notifiable, 'getResetPasswordUrl')) {
            return $message->action(
                __('Set Password'),
                $notifiable->getResetPasswordUrl($this->token)
            );
        }

        if (empty(config('filament-invite.routes.reset'))) {
            $url = Filament::getPanel('admin')->getResetPasswordUrl($this->token, $notifiable, ['invite' => true]);
        } else {
            $domain = rtrim(Filament::getPanel($this->filamentPanelId)->getPath(), '/');
            $url = url($domain . '/' . route(config('filament-invite.routes.reset'), [
                'token' => $this->token,
                'email' => $email,
                'invite' => true,
            ], false));
        }

        return $message->action(__('Set Password'), $url);
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
