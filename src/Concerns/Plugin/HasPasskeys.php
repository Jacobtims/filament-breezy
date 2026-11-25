<?php

namespace Jeffgreco13\FilamentBreezy\Concerns\Plugin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Models\Passkey;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

trait HasPasskeys
{
    protected bool $passkeys = false;

    protected bool $scopePasskeysToPanel = true;

    protected string $passkeyRelyingPartyName = '';

    protected string $passkeyRelyingPartyId = '';

    protected ?string $passkeyRelyingPartyIcon = null;

    protected bool $autoPromptPasskeys = false;

    protected ?string $residentKey = null;

    protected ?string $authenticatorAttachment = null;

    protected ?string $userVerification = null;

    public function enablePasskeys(bool $condition = true, ?string $relyingPartyName = null, ?string $relyingPartyId = null, ?string $relyingPartyIcon = null, bool $scopeToPanel = true, bool $autoPrompt = false, ?string $residentKey = null, ?string $authenticatorAttachment = null, ?string $userVerification = null): static
    {
        $this->passkeys = $condition;
        $this->scopePasskeysToPanel = $scopeToPanel;
        $this->passkeyRelyingPartyName = $relyingPartyName ?? config('app.name');
        $this->passkeyRelyingPartyId = $relyingPartyId ?? request()->getHost();
        $this->passkeyRelyingPartyIcon = $relyingPartyIcon;
        $this->autoPromptPasskeys = $autoPrompt;
        $this->residentKey = $residentKey;
        $this->authenticatorAttachment = $authenticatorAttachment;
        $this->userVerification = $userVerification;

        return $this;
    }

    public function scopePasskeysToPanel(): bool
    {
        return $this->scopePasskeysToPanel;
    }

    public function passkeyRelyingPartyName(): string
    {
        return $this->passkeyRelyingPartyName;
    }

    public function passkeyRelyingPartyId(): string
    {
        return $this->passkeyRelyingPartyId;
    }

    public function passkeyRelyingPartyIcon(): ?string
    {
        return $this->passkeyRelyingPartyIcon;
    }

    public function autoPromptPasskeys(): bool
    {
        return $this->autoPromptPasskeys;
    }

    public function residentKey(): ?string
    {
        return $this->residentKey;
    }

    public function authenticatorAttachment(): ?string
    {
        return $this->authenticatorAttachment;
    }

    public function userVerification(): ?string
    {
        return $this->userVerification;
    }

    public function passkeySerializer(): Serializer
    {
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();

        /** @var Serializer $serializer */
        $serializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();

        return $serializer;
    }

    public function passkeyRelatedPartyEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            name: $this->passkeyRelyingPartyName(),
            id: $this->passkeyRelyingPartyId(),
            icon: $this->passkeyRelyingPartyIcon(),
        );
    }

    public function passkeyGenerateUserEntity(): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            name: $this->auth()->user()?->email,
            id: $this->auth()->user()?->id,
            displayName: $this->auth()->user()?->name,
        );
    }

    public function generatePasskeyRegisterOptions(): string
    {
        $options = new PublicKeyCredentialCreationOptions(
            rp: $this->passkeyRelatedPartyEntity(),
            user: $this->passkeyGenerateUserEntity(),
            challenge: Str::random(),
            authenticatorSelection: new AuthenticatorSelectionCriteria(
                authenticatorAttachment: $this->authenticatorAttachment(),
                userVerification: $this->userVerification() ?? AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                residentKey: $this->residentKey(),
            ),
        );

        return $this->passkeySerializer()->serialize($options, 'json');
    }

    public function generatePasskeyAuthenticationOptions(): string
    {
        $options = new PublicKeyCredentialRequestOptions(
            challenge: Str::random(),
            rpId: $this->passkeyRelyingPartyId(),
            allowCredentials: [],
            userVerification: $this->userVerification(),
        );

        return $this->passkeySerializer()->serialize($options, 'json');
    }

    public function storePasskey(Authenticatable $authenticatable, string $passkeyJson, string $passkeyOptionsJson, string $hostName, array $additionalProperties = []): Passkey
    {
        $publicKeyCredentialSource = $this->passkeyDeterminePublicKeyCredentialSource(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName
        );

        /** @var Passkey $passkey */
        $passkey = Passkey::create([
            ...$additionalProperties,
            'authenticatable_id' => $this->auth()->id(),
            'authenticatable_type' => $this->auth()->user()->getMorphClass(),
            'data' => $publicKeyCredentialSource,
        ]);

        return $passkey;
    }

    public function passkeyDeterminePublicKeyCredentialSource(string $passkeyJson, string $passkeyOptionsJson, string $hostName): mixed
    {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);

        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new \Exception('The given passkey is not a valid public key credential.');
        }

        $csmFactory = new CeremonyStepManagerFactory;
        $creationCsm = $csmFactory->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                $publicKeyCredential->response,
                $passkeyOptions,
                $hostName,
            );
        } catch (Throwable $exception) {
            throw new \Exception('The given passkey could not be validated.');
        }

        return $publicKeyCredentialSource;
    }

    protected function getPasskeyOptions(string $passkeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (! json_validate($passkeyOptionsJson)) {
            throw new \Exception('The given passkey should be formatted as json.');
        }

        /** @var PublicKeyCredentialCreationOptions $passkeyOptions */
        $passkeyOptions = $this->passkeySerializer()->deserialize(
            $passkeyOptionsJson,
            PublicKeyCredentialCreationOptions::class,
            'json',
        );

        return $passkeyOptions;
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (! json_validate($passkeyJson)) {
            throw new \Exception('The given passkey should be formatted as json.');
        }

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = $this->passkeySerializer()->deserialize(
            $passkeyJson,
            PublicKeyCredential::class,
            'json',
        );

        return $publicKeyCredential;
    }

    public function findPasskeyToAuthenticate(string $publicKeyCredentialJson, string $passkeyOptionsJson): ?Passkey
    {
        $publicKeyCredential = $this->determinePublicKeyCredential($publicKeyCredentialJson);

        if (! $publicKeyCredential) {
            return null;
        }

        $passkey = $this->findPasskey($publicKeyCredential);

        if (! $passkey) {
            return null;
        }

        /** @var PublicKeyCredentialRequestOptions $passkeyOptions */
        $passkeyOptions = $this->passkeySerializer()->deserialize(
            $passkeyOptionsJson,
            PublicKeyCredentialRequestOptions::class,
            'json',
        );

        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $publicKeyCredential,
            $passkeyOptions,
            $passkey,
        );

        if (! $publicKeyCredentialSource) {
            return null;
        }

        $this->updatePasskey($passkey, $publicKeyCredentialSource);

        return $passkey;
    }

    public function determinePublicKeyCredential(string $publicKeyCredentialJson): ?PublicKeyCredential
    {
        $publicKeyCredential = $this->passkeySerializer()->deserialize(
            $publicKeyCredentialJson,
            PublicKeyCredential::class,
            'json',
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return null;
        }

        return $publicKeyCredential;
    }

    protected function findPasskey(PublicKeyCredential $publicKeyCredential): ?Passkey
    {
        return Passkey::firstWhere('credential_id', mb_convert_encoding($publicKeyCredential->rawId, 'UTF-8'));
    }

    protected function determinePublicKeyCredentialSource(PublicKeyCredential $publicKeyCredential, PublicKeyCredentialRequestOptions $passkeyOptions, Passkey $passkey): mixed
    {
        $csmFactory = new CeremonyStepManagerFactory;
        $requestCsm = $csmFactory->requestCeremony();

        try {
            $validator = AuthenticatorAssertionResponseValidator::create($requestCsm);

            $publicKeyCredentialSource = $validator->check(
                $passkey->data,
                $publicKeyCredential->response,
                $passkeyOptions,
                request()->getHost(),
                null,
            );
        } catch (Throwable) {
            return null;
        }

        return $publicKeyCredentialSource;
    }

    protected function updatePasskey(Passkey $passkey, mixed $publicKeyCredentialSource): self
    {
        $passkey->update([
            'data' => $publicKeyCredentialSource,
            'last_used_at' => now(),
        ]);

        return $this;
    }
}
