<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Resources\Value\Resource;
use Hawk\AuthClient\Resources\Value\ResourceBuilder;
use Hawk\AuthClient\Resources\Value\ResourceConstraints;
use Hawk\AuthClient\Resources\Value\ResourceList;
use Hawk\AuthClient\Users\Value\User;

interface ResourceLayerInterface
{
    /**
     * Retrieves a single resource by its uuid or unique name. If the resource is not found, returns null.
     *
     * @param string|\Stringable $identifier The resource's uuid or name.
     * @return Resource|null
     */
    public function getOne(string|\Stringable $identifier): Resource|null;

    /**
     * Returns a list of all resources. If constraints are provided, filters the list.
     *
     * @param ResourceConstraints|null $constraints Optional constraints to filter the list.
     * @return ResourceList
     */
    public function getAll(ResourceConstraints|null $constraints = null): iterable;

    /**
     * Deletes a resource from the storage. If the resource does not exist, does nothing.
     * @param Resource $resource The resource to remove.
     * @return void
     */
    public function remove(Resource $resource): void;

    /**
     * This method lets you create new resources, or update existing ones.
     * Note, this method requires your client to have the "hawk-manage-resource-permissions" service role!
     *
     * If the resource does not exist, it will be created.
     * Make sure to call the {@see ResourceBuilder::save()} method to persist the resource.
     *
     * example usage:
     * ```php
     * $client = new AuthClient();
     *
     * // Create a new resource
     * $client->resources()->define('my-resource')
     *      ->setDisplayName('My Resource')
     *      ->withOwner($client->users()->getOne('user-id'))
     *      ->addScope('read')
     *      ->addScope('write')
     *      ->save();
     *
     * // Update an existing resource
     * $resource = $client->resources()->getOne('my-resource');
     * $client->resources()->define($resource)
     *      ->setDisplayName('My Updated Resource')
     *      ->addScope('delete')
     *      ->save();
     * ```
     *
     * A word of caution: After you modified a resource, make sure your own code pulls the resource again from the storage,
     * because existing instances fetched with {@see self::getOne()} or {@see self::getAll()} will not reflect the changes!
     *
     * @param string|\Stringable|Resource $identifier The resource's uuid, name, or the resource itself.
     * @return ResourceBuilder
     */
    public function define(string|\Stringable|Resource $identifier): ResourceBuilder;

    /**
     * This method allows your client to manage how users that are not the resource owner can access the resource.
     * With this feature you can define which scopes a user has on a resource and with that what they can do with it.
     *
     * Note, this method requires your client to have the "hawk-manage-resource-permissions" service role!
     * Note too, that this method is rather powerful, your application should check carefully if a resource can be shared with a user.
     *
     * example usage:
     *
     * ```php
     * $client = new AuthClient();
     *
     * // Create a new share
     * $userToShareResourceWith = $client->users()->getOne('user-id');
     * $resourceToShare = $client->resources()->getOne('resource-id');
     * $client->resources()->shareWithUser($resourceToShare, $userToShareResourceWith, ['read', 'write']);
     *
     * // Update an existing share
     * $userToShareResourceWith = $client->users()->getOne('user-id');
     * $resourceToShare = $client->resources()->getOne('resource-id');
     * $client->resources()->shareWithUser($resourceToShare, $userToShareResourceWith, ['read', 'write', 'delete']);
     *
     * // Remove a share
     * $userToShareResourceWith = $client->users()->getOne('user-id');
     * $resourceToShare = $client->resources()->getOne('resource-id');
     * $client->resources()->shareWithUser($resourceToShare, $userToShareResourceWith, null);
     * ```
     * @param Resource $resource
     * @param User $user
     * @param array|null $scopes
     * @return void
     */
    public function shareWithUser(
        Resource   $resource,
        User       $user,
        array|null $scopes
    ): void;
}
