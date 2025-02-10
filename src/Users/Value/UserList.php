<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Users\Value;


use Hawk\AuthClient\Util\AbstractChunkedList;

/**
 * Lazy loading list of users by their ids. This list will load users in chunks of 50.
 * @extends AbstractChunkedList<User>
 */
class UserList extends AbstractChunkedList
{
}
