<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Util\AbstractChunkedList;

/**
 * Lazy loaded list of ResourceUser, which is a user with its associated scopes.
 * @extends AbstractChunkedList<ResourceUser>
 */
class ResourceUserList extends AbstractChunkedList
{
}
