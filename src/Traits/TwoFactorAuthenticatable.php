<?php

namespace Jeffgreco13\FilamentBreezy\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Models\BreezySession;

trait TwoFactorAuthenticatable
{
    public function initializeTwoFactorAuthenticatable(): void
    {
        $this->mergeCasts([
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ]);
    }

    public function hasEnabledTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret);
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    public function enableTwoFactorAuthentication(): void
    {
        $this->update([
            'two_factor_secret' => filament('filament-breezy')->getEngine()->generateSecretKey(),
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ]);
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function confirmTwoFactorAuthentication(): void
    {
        $this->update([
            'two_factor_confirmed_at' => now(),
        ]);

        $this->setTwoFactorSession();
    }

    public function setTwoFactorSession(): void
    {
        session(['breezy_session_id' => md5($this->id)]);
    }

    public function hasValidTwoFactorSession(): bool
    {
        return session()->has('breezy_session_id') && session('breezy_session_id') == md5($this->id);
    }

    public function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return Str::random(10).'-'.Str::random(10);
        })->all();
    }

    public function destroyRecoveryCode(string $recoveryCode): void
    {
        $unusedCodes = array_filter($this->two_factor_recovery_codes ?? [], fn ($code) => $code !== $recoveryCode);

        $this->forceFill([
            'two_factor_recovery_codes' => $unusedCodes ?: null,
        ])->save();
    }

    public function getTwoFactorQrCodeUrl(): string
    {
        return filament('filament-breezy')->getQrCodeUrl(
            config('app.name'),
            $this->email,
            $this->two_factor_secret
        );
    }

    public function reGenerateRecoveryCodes(): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ])->save();
    }
}
