<?php

namespace Jeffgreco13\FilamentBreezy\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Controllers\LogoutController;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Livewire\Features\SupportRedirects\Redirector;

class TwoFactorPage extends SimplePage implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected string $view = 'filament-breezy::filament.pages.two-factor';

    protected bool $hasTopbar = false;

    public $usingRecoveryCode = false;

    public $code;

    public array $data = []; // holds form state

    #[Url]
    public ?string $next;

    public function getTitle(): string
    {
        return __('filament-breezy::default.two_factor.heading');
    }

    public function getSubheading(): string
    {
        return __('filament-breezy::default.two_factor.description');
    }

    public function mount()
    {
        if (! Filament::auth()->check()) {
            return redirect()->to(Filament::getLoginUrl());
        } elseif (filament('filament-breezy')->auth()->user()->hasValidTwoFactorSession()) {
            return redirect()->to(Filament::getHomeUrl());
        }
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->label($this->usingRecoveryCode ? __('filament-breezy::default.fields.2fa_recovery_code') : __('filament-breezy::default.fields.2fa_code'))
                ->placeholder($this->usingRecoveryCode ? __('filament-breezy::default.two_factor.recovery_code_placeholder') : __('filament-breezy::default.two_factor.code_placeholder'))
                ->hint(new HtmlString(Blade::render('
                    <x-filament::link href="#" wire:click="toggleRecoveryCode()">'.($this->usingRecoveryCode ? __('filament-breezy::default.cancel') : __('filament-breezy::default.two_factor.recovery_code_link')).'
                    </x-filament::link>')))
                ->required()
                ->extraInputAttributes(['class' => 'text-center', 'autocomplete' => $this->usingRecoveryCode ? 'off' : 'one-time-code'])
                ->autofocus()
                ->suffixAction(
                    Action::make('cancel')
                        ->ToolTip(__('filament-breezy::default.cancel'))
                        ->icon('heroicon-o-x-circle')
                        ->action(function () {
                            Filament::auth()->logout();
                            $this->mount();
                        })
                ),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getFormActions())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public function toggleRecoveryCode()
    {
        $this->resetErrorBag('code');
        $this->code = null;
        $this->usingRecoveryCode = ! $this->usingRecoveryCode;
    }

    public function hasValidCode(): bool
    {
        if ($this->usingRecoveryCode) {
            return $this->code && filament('filament-breezy')->verifyRecoveryCode(code: $this->code);
        } else {
            return $this->code && filament('filament-breezy')->verify(code: $this->code);
        }
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    public function logout()
    {
        return app(LogoutController::class);
    }

    public function authenticate(): RedirectResponse|Redirector|null
    {
        $code = data_get($this->data, 'code');

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('data.code', __('filament::login.messages.throttled', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]));

            return null;
        }

        $this->code = $code; // sync for validation logic

        if (! $this->hasValidCode()) {
            $this->addError('data.code', __('filament-breezy::default.profile.2fa.confirmation.invalid_code'));

            return null;
        }

        if ($this->usingRecoveryCode) {
            Filament::auth()->user()->destroyRecoveryCode($this->code);
        }

        Filament::auth()->user()->setTwoFactorSession();

        return redirect()->to($this->next ?? Filament::getHomeUrl());
    }
}
