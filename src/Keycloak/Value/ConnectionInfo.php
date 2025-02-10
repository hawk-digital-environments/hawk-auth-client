<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Value;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class ConnectionInfo implements \JsonSerializable
{
    protected string $keycloakVersion;
    protected string $extensionVersion;
    protected string $clientId;
    protected ClientUuid $clientUuid;

    public function __construct(
        string     $keycloakVersion,
        string     $extensionVersion,
        string     $clientId,
        ClientUuid $clientUuid
    )
    {
        $this->keycloakVersion = $keycloakVersion;
        $this->extensionVersion = $extensionVersion;
        $this->clientId = $clientId;
        $this->clientUuid = $clientUuid;
    }

    /**
     * Returns the version of the Keycloak server
     * @return string
     */
    public function getKeycloakVersion(): string
    {
        return $this->keycloakVersion;
    }

    /**
     * Returns the version of the hawk auth-server extension
     * @return string
     */
    public function getExtensionVersion(): string
    {
        return $this->extensionVersion;
    }

    /**
     * Returns the id of this client. Should be the same as {@see ConnectionConfig::getClientId()}
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Returns the uuid of this client. Sometimes needed for requests
     * @return ClientUuid
     */
    public function getClientUuid(): ClientUuid
    {
        return $this->clientUuid;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['keycloakVersion'],
            $data['extensionVersion'],
            $data['clientId'],
            new ClientUuid($data['clientUuid'])
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'keycloakVersion' => $this->keycloakVersion,
            'extensionVersion' => $this->extensionVersion,
            'clientId' => $this->clientId,
            'clientUuid' => (string)$this->clientUuid
        ];
    }
}
