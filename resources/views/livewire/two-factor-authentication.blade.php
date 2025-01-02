<x-filament-breezy::grid-section md=2 :title="__('filament-breezy::default.profile.2fa.title')" :description="__('filament-breezy::default.profile.2fa.description')">

    <x-filament::card>

        @if($this->showRequiresTwoFactorAlert())

            <div style="{{ \Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables('danger',shades: [300, 400, 500, 600])]) }}" class="p-4 rounded bg-custom-500">
                <div class="flex">
                    <div class="flex-shrink-0">
                        @svg('heroicon-s-shield-exclamation', 'w-5 h-5 text-danger-600')
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-danger-500">
                            {{ __('filament-breezy::default.profile.2fa.must_enable') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @unless ($user->hasEnabledTwoFactor())
            <h3 class="flex items-center gap-2 text-lg font-medium">
                @svg('heroicon-o-exclamation-circle', 'w-6')
                {{__('filament-breezy::default.profile.2fa.not_enabled.title') }}
            </h3>
            <p class="text-sm">{{ __('filament-breezy::default.profile.2fa.not_enabled.description') }}</p>

            <div class="flex justify-between mt-3">
                {{ $this->enableAction }}
            </div>

        @else
            @if ($user->hasConfirmedTwoFactor())
                <h3 class="flex items-center gap-2 text-lg font-medium">
                    @svg('heroicon-o-shield-check', 'w-6')
                    {{ __('filament-breezy::default.profile.2fa.enabled.title') }}
                </h3>
                <p class="text-sm">{{ __('filament-breezy::default.profile.2fa.enabled.description') }}</p>
                @if($showRecoveryCodes)
                    <div class="px-4 space-y-3">
                        <p class="text-xs">{{ __('filament-breezy::default.profile.2fa.enabled.store_codes') }}</p>
                        <div>
                            @foreach ($this->recoveryCodes->toArray() as $code )
                            <span class="inline-flex items-center p-1 text-xs font-medium text-gray-800 dark:text-gray-400 bg-gray-100 rounded-full dark:bg-gray-900">{{ $code }}</span>
                            @endforeach
                        </div>
                        <div class="inline-block text-xs">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </div>
                    </div>
                @endif
                <div class="flex justify-between mt-3">
                    {{ $this->regenerateCodesAction }}
                    {{ $this->disableAction()->color('danger') }}
                </div>
            @else
                <h3 class="flex items-center gap-2 text-lg font-medium">
                    @svg('heroicon-o-question-mark-circle', 'w-6')
                    {{ __('filament-breezy::default.profile.2fa.finish_enabling.title') }}
                </h3>
                <p class="text-sm">{{ __('filament-breezy::default.profile.2fa.finish_enabling.description') }}</p>
                <div class="flex mt-3 space-x-4 divide-x">
                    <div>
                        {!! $this->getTwoFactorQrCode() !!}
                        <p class="pt-2 text-sm">{{ __('filament-breezy::default.profile.2fa.setup_key') }} {{ $this->user->two_factor_secret }}</p>
                    </div>
                    <div class="px-4 space-y-3">
                        <p class="text-xs">{{ __('filament-breezy::default.profile.2fa.enabled.store_codes') }}</p>
                        <div>
                        @foreach ($this->recoveryCodes->toArray() as $code )
                            <span class="inline-flex items-center p-1 text-xs font-medium text-gray-800 dark:text-gray-400 bg-gray-100 rounded-full dark:bg-gray-900">{{ $code }}</span>
                        @endforeach
                        </div>
                        <div class="inline-block text-xs">
                            <x-filament-breezy::clipboard-link :data="$this->recoveryCodes->join(',')" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-3">
                    {{ $this->confirmAction }}
                    {{ $this->disableAction }}
                </div>

            @endif

        @endunless
    </x-filament::card>
    <x-filament-actions::modals />
</x-filament-breezy::grid-section>
