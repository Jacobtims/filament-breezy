<?php

namespace Jeffgreco13\FilamentBreezy\Tests\Fixtures;

use Illuminate\Support\Str;
use OpenSSLAsymmetricKey;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Exercise the real WebAuthn verifier without a physical authenticator.
 * The generated private key stays in the test; only its public key is stored.
 */
class PasskeyAuthenticator
{
    private OpenSSLAsymmetricKey $key;

    private string $credentialId;

    private string $publicKey;

    public function __construct()
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        if (! $key instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('Could not create the synthetic authenticator.');
        }

        $this->key = $key;
        $this->credentialId = (string) Str::uuid();
        $details = openssl_pkey_get_details($key);

        // COSE EC2 / ES256 with P-256 public coordinates.
        $this->publicKey = hex2bin('a5010203262001215820').$details['ec']['x'].hex2bin('225820').$details['ec']['y'];
    }

    public function publicKeySource(string $userHandle): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            publicKeyCredentialId: $this->credentialId,
            type: 'public-key',
            transports: ['internal'],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $this->publicKey,
            userHandle: $userHandle,
            counter: 0,
        );
    }

    /** Change the signed verification bit while keeping the assertion valid. */
    public function assertion(string $optionsJson, string $userHandle, bool $verified): string
    {
        $options = json_decode($optionsJson, true, flags: JSON_THROW_ON_ERROR);
        $clientData = $this->clientData('webauthn.get', $options['challenge'], $options['rpId']);
        $authenticatorData = hash('sha256', $options['rpId'], true).chr($verified ? 0x05 : 0x01).pack('N', 1);
        openssl_sign($authenticatorData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $this->credential([
            'clientDataJSON' => self::base64($clientData),
            'authenticatorData' => self::base64($authenticatorData),
            'signature' => self::base64($signature),
            'userHandle' => self::base64($userHandle),
        ]);
    }

    /** A valid "none" attestation isolates the registration verification policy. */
    public function registration(string $optionsJson, bool $verified): string
    {
        $options = json_decode($optionsJson, true, flags: JSON_THROW_ON_ERROR);
        $clientData = $this->clientData('webauthn.create', $options['challenge'], $options['rp']['id']);
        $authenticatorData = hash('sha256', $options['rp']['id'], true)
            .chr($verified ? 0x45 : 0x41).pack('N', 0)
            .str_repeat("\0", 16).pack('n', strlen($this->credentialId)).$this->credentialId.$this->publicKey;

        // CBOR map: fmt = none, empty attStmt, and the binary authenticator data.
        $attestation = hex2bin('a363666d74646e6f6e656761747453746d74a068617574684461746158')
            .chr(strlen($authenticatorData)).$authenticatorData;

        return $this->credential([
            'clientDataJSON' => self::base64($clientData),
            'attestationObject' => self::base64($attestation),
            'transports' => ['internal'],
        ]);
    }

    private function clientData(string $type, string $challenge, string $relyingPartyId): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => 'https://'.$relyingPartyId,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $response */
    private function credential(array $response): string
    {
        return json_encode([
            'id' => self::base64($this->credentialId),
            'rawId' => self::base64($this->credentialId),
            'type' => 'public-key',
            'response' => $response,
            'clientExtensionResults' => new \stdClass,
        ], JSON_THROW_ON_ERROR);
    }

    /** WebAuthn uses unpadded, URL-safe base64 for binary fields. */
    private static function base64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
