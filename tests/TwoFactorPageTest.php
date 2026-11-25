<?php

use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;
use Jeffgreco13\FilamentBreezy\Tests\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    $this->loadLaravelMigrations();

    (include __DIR__.'/../database/migrations/create_breezy_sessions_table.php.stub')->up();

    $this->actingAs(TestUser::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]));
});

it('labels the submit button with the authenticate action label', function () {
    app()->setLocale('pt_BR');

    Livewire::test(TwoFactorPage::class)
        ->assertActionExists('authenticate')
        ->assertActionHasLabel('authenticate', __('filament-panels::auth/pages/login.form.actions.authenticate.label'))
        ->assertSee(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
        ->assertDontSee(__('filament-breezy::default.fields.login'))
        ->assertDontSee('filament-panels::');
});

it('submits the form to the authenticate method', function () {
    Livewire::test(TwoFactorPage::class)
        ->assertSeeHtml('wire:submit="authenticate"');
});
