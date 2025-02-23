<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Groups\Value;

use ArrayIterator;
use Hawk\AuthClient\Util\AbstractList;
use Hawk\AuthClient\Util\Uuid;

/**
 * @extends AbstractList<Group>
 */
class GroupList extends AbstractList
{
    public function __construct(Group ...$groups)
    {
        $this->items = $groups;
    }

    /**
     * Similar to {@see getIterator()} but iterates over all groups in the list, including children.
     * @return \Iterator<Group>
     */
    public function getRecursiveIterator(): \Iterator
    {
        $resolver = function () {
            foreach ($this->items as $group) {
                yield $group;
                foreach ($group->getChildren() as $child) {
                    yield $child;
                    yield from $child->getChildren()->getRecursiveIterator();
                }
            }
        };

        return new ArrayIterator(iterator_to_array($resolver(), false));
    }

    /**
     * Creates a hierarchical group list from a cached array.
     *
     * @param array $groups
     * @return self
     * @internal This method is not part of the public API and should not be used outside of the library.
     */
    public static function fromScalarList(array ...$groups): self
    {
        $buildRecursively = static function (array $groups, callable $buildRecursively): array {
            return array_map(static function (array $group) use ($buildRecursively) {
                return new Group(
                    new Uuid($group['id']),
                    $group['name'],
                    $group['path'],
                    new GroupList(
                        ...$buildRecursively(
                        $group['children'] ?? $group['subGroups'] ?? [],
                        $buildRecursively
                    ))
                );
            }, $groups);
        };

        return new self(...$buildRecursively($groups, $buildRecursively));
    }
}
