<?php
declare(strict_types=1);

namespace Hawk\AuthClient\Groups\Value;


use Hawk\AuthClient\Groups\GroupReferenceTypeEnum;
use Hawk\AuthClient\Util\AbstractList;

/**
 * @extends AbstractList<GroupReference>
 */
class GroupReferenceList extends AbstractList
{
    public function __construct(GroupReference ...$groupReferences)
    {
        $this->items = $groupReferences;
    }

    /**
     * Checks if the list contains any of the given groups. The groups can be given as string, {@see GroupReference} or {@see Group}.
     * @param string|\Stringable|GroupReference|Group ...$groups
     * @return bool
     */
    public function hasAny(string|\Stringable|GroupReference|Group ...$groups): bool
    {
        return $this->hasAnyInternal($groups, false);
    }

    /**
     * Checks if the list contains any of the given groups or any child of the given groups. The groups can be given as string, {@see GroupReference} or {@see Group}.
     * @param string|\Stringable|GroupReference|Group ...$groups
     * @return bool
     */
    public function hasAnyOrHasChildOfAny(string|\Stringable|GroupReference|Group ...$groups): bool
    {
        return $this->hasAnyInternal($groups, true);
    }

    /**
     * Creates a new instance from a list of strings.
     * @param string[] $values
     * @return static
     * @internal This method is not part of the public API and should not be used outside the library.
     */
    public static function fromScalarList(string ...$values): self
    {
        return new self(...array_map(static fn(string $value) => new GroupReference($value), $values));
    }

    protected function hasAnyInternal(array $groups, bool $includeParents): bool
    {
        $comparePaths = static function (string $pathA, string $pathB) use ($includeParents): bool {
            $valANormalized = ltrim($pathA, '/');
            $valBNormalized = ltrim($pathB, '/');
            if ($valANormalized === $valBNormalized) {
                return true;
            }

            if ($includeParents && str_starts_with($valBNormalized, $valANormalized)) {
                return true;
            }

            return false;
        };

        foreach ($groups as $givenGroup) {
            // Special handling if a GroupInterface is given, because we can have more options to compare on
            if ($givenGroup instanceof Group) {
                foreach ($this->items as $ref) {
                    if ($ref->getType() === GroupReferenceTypeEnum::ID && (string)$ref === $givenGroup->getId()) {
                        return true;
                    }
                    if ($ref->getType() === GroupReferenceTypeEnum::NAME && (string)$ref === $givenGroup->getName()) {
                        return true;
                    }
                    if ($ref->getType() === GroupReferenceTypeEnum::PATH && $comparePaths((string)$ref, $givenGroup->getPath())) {
                        return true;
                    }
                }

                continue;
            }

            // Default handling
            $givenGroup = $givenGroup instanceof GroupReference ? $givenGroup : new GroupReference((string)$givenGroup);
            foreach ($this->items as $groupRefA) {
                if ($comparePaths((string)$givenGroup, (string)$groupRefA)) {
                    return true;
                }
            }
        }

        return false;
    }
}
