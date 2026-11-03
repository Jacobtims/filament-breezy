<?php

use Illuminate\Support\Facades\Schema;
use Jeffgreco13\FilamentBreezy\Models\Passkey;
use Jeffgreco13\FilamentBreezy\Tests\Fixtures\PasskeyAuthenticator;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function () {
    $this->loadLaravelMigrations();
    (require __DIR__.'/../database/migrations/create_passkeys_table.php.stub')->up();
    $this->user = UserFactory::new()->create();
    $this->actingAs($this->user);
    $this->plugin = filament('filament-breezy')->enablePasskeys(relyingPartyId: 'localhost');
});

afterEach(function () {
    Schema::dropIfExists('passkeys');
});

it('keeps the existing browser verification defaults when the option is omitted', function () {
    $registration = json_decode($this->plugin->generatePasskeyRegisterOptions(), true);
    $authentication = json_decode($this->plugin->generatePasskeyAuthenticationOptions(), true);

    expect($registration)->not->toHaveKey('authenticatorSelection');
    expect($authentication)->not->toHaveKey('userVerification');
});

it('requests user verification for registration and sign-in when enabled', function () {
    $this->plugin->enablePasskeys(requireUserVerification: true);

    $registration = json_decode($this->plugin->generatePasskeyRegisterOptions(), true);
    $authentication = json_decode($this->plugin->generatePasskeyAuthenticationOptions(), true);

    expect($registration['authenticatorSelection']['userVerification'])->toBe('required');
    expect($authentication['userVerification'])->toBe('required');
});

it('allows registration and sign-in without user verification when enforcement is disabled', function () {
    $this->plugin->enablePasskeys(requireUserVerification: false);
    $authenticator = new PasskeyAuthenticator;
    $options = $this->plugin->generatePasskeyRegisterOptions();
    $passkey = $this->plugin->storePasskey(
        $this->user,
        $authenticator->registration($options, verified: false),
        $options,
        'localhost',
        ['name' => 'Synthetic authenticator'],
    );
    $options = $this->plugin->generatePasskeyAuthenticationOptions();

    $result = $this->plugin->findPasskeyToAuthenticate(
        $authenticator->assertion($options, (string) $this->user->id, verified: false),
        $options,
    );

    expect($result?->is($passkey))->toBeTrue();
    expect($passkey->fresh()->last_used_at)->not->toBeNull();
});

it('accepts verified registration and sign-in when enforcement is enabled', function () {
    $this->plugin->enablePasskeys(requireUserVerification: true);
    $authenticator = new PasskeyAuthenticator;
    $options = $this->plugin->generatePasskeyRegisterOptions();
    $passkey = $this->plugin->storePasskey(
        $this->user,
        $authenticator->registration($options, verified: true),
        $options,
        'localhost',
        ['name' => 'Synthetic authenticator'],
    );
    $options = $this->plugin->generatePasskeyAuthenticationOptions();

    $result = $this->plugin->findPasskeyToAuthenticate(
        $authenticator->assertion($options, (string) $this->user->id, verified: true),
        $options,
    );

    expect($result?->is($passkey))->toBeTrue();
    expect($passkey->fresh()->last_used_at)->not->toBeNull();
});

it('rejects an unverified registration even if the browser removes the requirement', function () {
    $this->plugin->enablePasskeys(requireUserVerification: true);
    $authenticator = new PasskeyAuthenticator;
    $options = $this->plugin->generatePasskeyRegisterOptions();
    $changed = json_decode($options, true);
    unset($changed['authenticatorSelection']);

    expect(fn () => $this->plugin->storePasskey(
        $this->user,
        $authenticator->registration($options, verified: false),
        json_encode($changed),
        'localhost',
        ['name' => 'Synthetic authenticator'],
    ))->toThrow(Exception::class, 'could not be validated');

    expect(Passkey::query()->count())->toBe(0);
});

it('rejects a signed assertion without verification even if the browser downgrades the requirement', function () {
    $this->plugin->enablePasskeys(requireUserVerification: true);
    $authenticator = new PasskeyAuthenticator;
    $passkey = Passkey::create([
        'authenticatable_type' => $this->user->getMorphClass(),
        'authenticatable_id' => $this->user->id,
        'name' => 'Synthetic authenticator',
        'data' => $authenticator->publicKeySource((string) $this->user->id),
    ]);
    $options = $this->plugin->generatePasskeyAuthenticationOptions();
    $changed = json_decode($options, true);
    $changed['userVerification'] = 'discouraged';

    $result = $this->plugin->findPasskeyToAuthenticate(
        $authenticator->assertion($options, (string) $this->user->id, verified: false),
        json_encode($changed),
    );

    expect($result)->toBeNull();
    expect($passkey->fresh()->last_used_at)->toBeNull();
    expect($passkey->fresh()->data->counter)->toBe(0);
});
