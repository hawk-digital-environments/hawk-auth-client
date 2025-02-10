<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles;


use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Layers\ProfileLayerInterface;
use Hawk\AuthClient\Profiles\Structure\ProfileStructureBuilder;
use Hawk\AuthClient\Users\Value\User;

class ProfileLayer implements ProfileLayerInterface
{
    protected ConnectionConfig $config;
    protected KeycloakApiClient $apiClient;
    protected ProfileStorage $storage;

    public function __construct(
        ConnectionConfig  $config,
        KeycloakApiClient $apiClient,
        ProfileStorage    $storage
    )
    {
        $this->config = $config;
        $this->apiClient = $apiClient;
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function define(): ProfileStructureBuilder
    {
        return new ProfileStructureBuilder(
            $this->config,
            $this->apiClient->fetchProfileStructure(),
            $this->apiClient
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function update(User $user): ProfileUpdater
    {
        return new ProfileUpdater(
            $user,
            $this->config,
            $this->storage
        );
    }
}
