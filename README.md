![Filament Breezy cover art](./art/breezy-banner.png)

# Enhanced security for Filament v4+ Panels.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffgreco13/filament-breezy.svg?style=flat-square)](https://packagist.org/packages/jeffgreco13/filament-breezy)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffgreco13/filament-breezy.svg?style=flat-square)](https://packagist.org/packages/jeffgreco13/filament-breezy)

Enhanced security features for Filament (v4) Panels. 
Includes a customizable My Profile page with personal info & avatar support, update password, two factor authentication, passkey authentication and Sanctum token management.
Installs in minutes!

## Features & Screenshots
My Profile - Personal info with avatar support
![Screenshot of Profile with avatar support](./art/profile-with-avatar.png)
Update password with customizable validation rules
![Screenshot of Two Factor codes](./art/password-update.png)
Protected sensitive actions with a password confirmation modal Action
![Screenshot of Password confirmation action](./art/actions-confirm-password.png)
Two factor authentication with recovery codes
![Screenshot of Two Factor codes](./art/2fa-confirm.png)
Force the user to enable two factor authentication before they can use the app
![Screenshot of forcing two factor auth](./art/2fa-must-enable.png)
Create and manage Sanctum personal access tokens
![Screenshot of Sanctum token management](./art/sanctum-manage-tokens.png)
![Screenshot of Sanctum token management](./art/sanctum-create.png)
Manage active browser sessions and log out other sessions  
![Screenshot of Browser Sessions](./art/browser-sessions.png)  
![Screenshot of Close Browser Sessions Confirmation](./art/close-browser-sessions-confirm-password.png)
Passkeys for passwordless authentication
![Screenshot of Passkeys](./art/passkeys-create.png)
![Authenticate using Passkey](./art/authenticate-using-passkey.png)

## Installation

1. Install the package via composer and install

```bash
composer require jeffgreco13/filament-breezy
php artisan breezy:install
```

2. Add Breezy to your Filament Panel

Add Breezy to a panel by adding the class to your Filament Panel's `plugin()` or `plugins([])` method.

```php
use Jeffgreco13\FilamentBreezy\BreezyCore;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            BreezyCore::make()
        ])
}
```

3. Integrate Tailwind classes

Filament recommends developers to [create a custom theme](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) to better support a plugin's additional Tailwind classes. 
After you have created your custom theme, add Breezy's views to your theme's `theme.css` file usually located in `resources/css/filament/admin/theme.css`:

```css
@source '../../../../vendor/jeffgreco13/filament-breezy/resources/**/*';
```

4. Optionally, you can publish the views

```bash
php artisan vendor:publish --tag="filament-breezy-views"
```

## Usage & Configuration

### Update the auth guard

Breezy will use the `authGuard` set on the Filament Panel that you create. You may update the authGuard as you please:

```php
use Jeffgreco13\FilamentBreezy\BreezyCore;

class CustomersPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ...
            ->authGuard('customers')
            ->plugin(
                BreezyCore::make()
            )
    }
}
```

**NOTE:** you must ensure that the model used in your Guard extends the Authenticatable class.

### My Profile

Enable the My Profile page with configuration options.

**NOTE:** if you are using avatars,

```php
BreezyCore::make()
    ->myProfile(
        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
        userMenuLabel: 'My Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
        navigationGroup: 'Settings', // Sets the navigation group for the My Profile page (default = null)
        hasAvatars: false, // Enables the avatar upload form component (default = false)
        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
    )
```

#### Custom My Profile page class

You can also use a custom My Profile page class by extending the default one, and registering it with the plugin.

```php
BreezyCore::make()
    ->myProfile()
    ->customMyProfilePage(AccountSettingsPage::class),
```

#### Using avatars in your Panel

The instructions for using custom avatars is found in the Filament v4 docs under [Setting up user avatars](https://filamentphp.com/docs/4.x/users/overview#setting-up-user-avatars).

Here is a possible implementation using the example from the docs:

```php
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    // ...

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }
}

```

#### Customize the avatar upload component


```php
use Filament\Forms\Components\FileUpload;

BreezyCore::make()
    ->avatarUploadComponent(fn($fileUpload) => $fileUpload->disableLabel())
    // OR, replace with your own component
    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('profile-photos'))
```

#### Add column to table

If you wish to have your own avatar, you need to create a column on the users table named `avatar_url`. It is reccomended that you create a new migration for it, and add the column there:

```bash
php artisan make:migration add_avatar_url_column_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_url');
        });
    }
};
```

#### Add column to user model
```php
    protected $fillable = [
        ...
        'avatar_url',
        ...
    ];
```


#### Customize password update

By default Breezy uses the [default password rules](https://laravel.com/docs/12.x/validation#defining-default-password-rules) defined in your Laravel project. 
You may customize the validation rules for the update password component by passing an array of validation strings, or an instance of the `Illuminate\Validation\Rules\Password` class.

```php
use Illuminate\Validation\Rules\Password;

BreezyCore::make()
    ->passwordUpdateRules(
        rules: [Password::default()->mixedCase()->uncompromised(3)], // you may pass an array of validation rules as well. (default = ['min:8'])
        requiresCurrentPassword: true, // when false, the user can update their password without entering their current password. (default = true)
    )

```

#### Exclude default My Profile components

If you don't want a default My Profile page component to be used, you can exclude them using the `withoutMyProfileComponents` helper.

```php
BreezyCore::make()
    ->withoutMyProfileComponents([
        'update_password'
    ])
```


#### Create custom My Profile components

You can create a custom Livewire components for the My Profile page and append them easily.

1. Create a new Livewire component in your project using:

```bash
php artisan make:livewire MyCustomComponent
```

2. Extend the `MyProfileComponent` class included with Breezy. This class implements Actions and Forms.

```php
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MyCustomComponent extends MyProfileComponent
{
    protected string $view = "livewire.my-custom-component";
    public array $only = ['my_custom_field'];
    public array $data;
    public $user;
    public $userClass;

    // this example shows an additional field we want to capture and save on the user
    public function mount()
    {
        $this->user = Filament::getCurrentPanel()->auth()->user();
        $this->userClass = get_class($this->user);

        $this->form->fill($this->user->only($this->only));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('my_custom_field')
                    ->required()
            ])
            ->statePath('data');
    }

    // only capture the custome component field
    public function submit(): void
    {
        $data = collect($this->form->getState())->only($this->only)->all();
        $this->user->update($data);
        Notification::make()
            ->success()
            ->title(__('Custom component updated successfully'))
            ->send();
    }
}
```

3. Within your Livewire component's view, you can use Filament's `<x-filament::section>` Blade component to match the style:

```bladehtml
<x-filament::section :aside="true" heading="Your title" description="This is the description">
    <form wire:submit.prevent="submit" class="space-y-6">

        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit" form="submit" class="align-right">
                Submit!
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
```

4. Finally, register your new component with Breezy:

```php
use App\Livewire\MyCustomComponent;

BreezyCore::make()
    ->myProfileComponents([MyCustomComponent::class])
```

#### Override My Profile components

You may override the existing MyProfile components to replace them with your own:

```php
use App\Livewire\MyCustomComponent;

BreezyCore::make()
    ->myProfileComponents([
        // 'personal_info' => ,
        'update_password' => MyCustomComponent::class, // replaces UpdatePassword component with your own.
        // 'two_factor_authentication' => ,
        // 'sanctum_tokens' =>
        // 'browser_sessions' =>
    ])
```

> [!IMPORTANT]
> Changing the email address on the personal info form requires the user to re-enter their current password. The confirmation field appears as soon as the email input differs from the user's saved address. This protects against an attacker with access to an authenticated session taking over the account by changing the login email.<br/>
> If you override `getProfileFormComponents()`, please keep `$this->getCurrentPasswordComponent()` in the returned array.

If you want to customize only the fields and notification in the personal info component, you can extend the original breezy component:

```php
namespace App\Livewire;

use Filament\Forms;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;

class CustomPersonalInfo extends PersonalInfo
{
    public ?array $only = ['custom_name_field', 'custom_email_field'];

    // You can override the default components by returning an array of components.
    protected function getProfileFormComponents(): array
    {
        return [
            $this->getNameComponent(),
            $this->getEmailComponent(),
            $this->getCurrentPasswordComponent(),
            $this->getCustomComponent(),
        ];
    }

    protected function getNameComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('custom_name_field')
            ->required();
    }

    protected function getEmailComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('custom_email_field')
            ->required();
    }

    protected function sendNotification(): void
    {
        Notification::make()
            ->success()
            ->title('Saved Data!')
            ->send();
    }
}

```
Now, as mentioned above, give this component to `BreezyCore::make()->myProfileComponents` to override the original and use your custom component.

#### Sorting My Profile components

Custom MyProfile components can be sorted by setting their static `$sort` property. This property can be set for existing MyProfile components in any service provider:

```php
TwoFactorAuthentication::setSort(4);
```

A lot of the time this won't be necessary, though, as the default sort order is spaced out in steps of 10, so there should be enough numbers to place any custom components in between.

### Two Factor Authentication

1. Add `Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable` to your Authenticatable model:

```php
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;
    // ...

}
```

2. Enable Two Factor Authentication using the `enableTwoFactorAuthentication()` method on the Breezy plugin.

```php
BreezyCore::make()
    ->enableTwoFactorAuthentication(
        force: false, // force the user to enable 2FA before they can use the application (default = false)
        action: CustomTwoFactorPage::class, // optionally, use a custom 2FA page
        authMiddleware: MustTwoFactor::class, // optionally, customize 2FA auth middleware or disable it to register manually by setting false
        scopeToPanel: true, // scope the 2FA only to the current panel (default = true)
    )
```

3. Adjust the 2FA page

The Breezy 2FA page can be swapped for a custom implementation (see above), same as the Filament auth pages. This allows, for example, to define a custom auth layout like so:

```php
use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;

class CustomTwoFactorPage extends TwoFactorPage
{
    protected static string $layout = 'custom.auth.layout.view';
}
```

### Sanctum Personal Access tokens

Since Laravel 11.x Sanctum is no longer included by default. If you don't already have the package installed follow the [installation instructions here](https://laravel.com/docs/sanctum#installation).

Enable the Sanctum token management component:

```php
BreezyCore::make()
    ->enableSanctumTokens(
        permissions: ['my','custom','permissions'] // optional, customize the permissions (default = ["create", "view", "update", "delete"])
    )
```

### Password Confirmation Button Action

This button action will prompt the user to enter their password for sensitive actions (eg. delete). This action uses the same `'password_timeout'` number of seconds found in `config/auth.php`.

```php
use Jeffgreco13\FilamentBreezy\Actions\PasswordButtonAction;
use Filament\Support\Icons\Heroicon;

PasswordButtonAction::make('secure_action')->action('doSecureAction')

// Customize the icon, action, modalHeading and anything else.
PasswordButtonAction::make('secure_action')->label('Delete')->icon(Heroicon::ShieldCheck)->modalHeading('Confirmation')->action(fn() => $this->doAction())
```

### Browser Sessions

Sanctum’s **Browser Sessions** feature, which is disabled by default, allows users to manage their active sessions on different devices and remotely log out of other browser sessions, enhancing account security. 
To enable this feature, you must use the `enableBrowserSessions` method. 
This requires your session driver to be `database`.

#### Enabling Browser Sessions

To enable the Browser Sessions feature, use the `enableBrowserSessions` method in `BreezyCore`:

```php
BreezyCore::make()
    ->enableBrowserSessions(condition: true) // Enable the Browser Sessions feature (default = true)
```

#### Viewing Active Sessions

On the user's profile page, active sessions are displayed with device information, including:

- Browser and platform of the device
- IP address
- Last activity of the session

#### Logging Out of Other Browser Sessions

Users can log out of other active sessions by entering their password for confirmation. This allows them to securely log out of all other active sessions on other devices.

#### Additional Configuration

If you want to customize the component or modify its behavior, you can override the `browser_sessions` component in the `myProfileComponents` method:

```php
BreezyCore::make()
    ->myProfileComponents([
        'browser_sessions' => \App\Livewire\CustomBrowserSessions::class, // Your custom component
    ])
```

### Passkeys

Passkeys let users securely manage their passkeys and sign in without passwords, providing faster and more secure authentication.

#### Enabling Passkeys
Enable passkey authentication using the `enablePasskeys()` method on the Breezy plugin.

```php
BreezyCore::make()
    ->enablePasskeys(
        relyingPartyName: 'My App',    // optional — relying party name shown to the user (default: APP_NAME)
        relyingPartyId: 'my-app.com',  // optional — relying party ID, must match the browser origin hostname (default: current host)
        relyingPartyIcon: '/logo.png', // optional — icon URL shown by the authenticator
        scopeToPanel: true,            // optional — scope passkeys to the current panel only (default: true)
        autoPrompt: false,             // optional — automatically prompt for passkey on the login page (default: false)
        residentKey: null,             // optional — controls passkey discoverability (default: no_preference)
        authenticatorAttachment: null, // optional — restrict to platform or cross-platform authenticators (default: no preference)
        userVerification: null,        // optional — require user verification (PIN/biometric) during registration and login (default: preferred)
    )
```

> **Note on `residentKey`:** For full compatibility with password managers such as Bitwarden or 1Password, set `residentKey` to `RESIDENT_KEY_REQUIREMENT_REQUIRED`. Without this, passkeys may be registered as non-discoverable, meaning password managers cannot find them during authentication. Platform authenticators like Apple Keychain are more lenient and work regardless.
>
> Available values:
> - `AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED` — discoverable, recommended for password manager compatibility
> - `AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED` — discoverable if supported by the authenticator
> - `AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED` — non-discoverable (hardware keys with limited storage)

> **Note on `authenticatorAttachment`:** Controls whether registration is restricted to a specific authenticator type. Only applies to registration (not login).
>
> Available values:
> - `AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM` — built-in authenticators only (e.g. Apple Keychain, Windows Hello)
> - `AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM` — external authenticators only (e.g. security keys, phones)
> - `null` — no preference (default)

> **Note on `userVerification`:** Controls whether the authenticator must verify the user (PIN, biometric) during both registration and login.
>
> Available values:
> - `AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED` — verification mandatory
> - `AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED` — verification requested if supported (default)
> - `AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED` — verification not requested
> - `null` / not set — no preference, authenticator decides (default)

#### Additional Configuration
If you want to customize the component or modify its behavior, you can override the passkeys component in the myProfileComponents method:

```php
BreezyCore::make()
    ->myProfileComponents([
        'passkeys' => \App\Livewire\CustomPasskeys::class, // Your custom component
    ])
```

## FAQ
> How do 2FA sessions work across multiple panels?

By default, Breezy uses the same guard as defined on your Panel. The default is 'web'. Only panels that have registered the BreezyCore plugin will have access to 2FA. If multiple panels use 2FA, and share the same guard, the User only has to enter the OTP once for the duration of the session.

> How does 2FA interact with MustVerifyEmail?

When 2FA is properly configured, and the User is prompted for the OTP code before email verification.

> How long does the 2FA session last?

The 2FA session is the same as the Laravel session lifetime. Once the user is logged out, or the session expires, they will need to enter the OTP code again.

## Upgrading

Please see the [UPGRADING](UPGRADING.md) guide for detailed upgrade instructions between major versions.

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

- [Jeff Greco](https://github.com/jeffgreco13)
- [Jacobtims](https://github.com/Jacobtims)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
