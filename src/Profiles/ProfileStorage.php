<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles;


use Hawk\AuthClient\Cache\CacheAdapterInterface;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Value\UserProfile;
use Hawk\AuthClient\Users\Value\User;

class ProfileStorage
{
    protected array $profiles = [];
    protected ConnectionConfig $config;
    protected CacheAdapterInterface $cache;
    protected KeycloakApiClient $keycloakApiClient;

    public function __construct(
        ConnectionConfig      $config,
        CacheAdapterInterface $cache,
        KeycloakApiClient     $keycloakApiClient
    )
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->keycloakApiClient = $keycloakApiClient;
    }

    /**
     * Returns the profile of the given user.
     * If the profile has already been resolved, it will be returned from the cache.
     * @param User $user The user to get the profile for.
     * @return UserProfile
     */
    public function getProfileOfUser(User $user): UserProfile
    {
        return $this->profiles[$user->getId()] ??= $this->cache->remember(
            'keycloak.profile.' . $user->getId(),
            valueGenerator: fn() => $this->keycloakApiClient->fetchUserProfile($user),
            valueToCache: fn(UserProfile $profile) => $profile->jsonSerialize(),
            cacheToValue: fn(array $data) => UserProfile::fromArray($this->config, $data)
        );
    }

    /**
     * Updates the profile of the given user.
     * @param User $user The user to update the profile for.
     * @param array $data The data to update the profile with.
     * @return void
     */
    public function updateProfile(User $user, array $data): void
    {
        $this->keycloakApiClient->updateUserProfile($user, $data);
    }

    /**
     * Removes all resolved profiles and will fetch them again the next time they are requested.
     * @return void
     * @internal This method is not part of the public API and should not be used by client code.
     */
    public function flushResolved(): void
    {
        $this->profiles = [];
    }
}
