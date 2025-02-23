<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;

use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Stringable;

/**
 * @internal This class is not part of the public API and may change without notice.
 */
class ProfileStructureData implements \JsonSerializable
{
    use ProfilePrefixTrait;

    protected array $data;
    protected bool $dirty = false;

    public function __construct(array $data)
    {
        if (!isset($data['attributes'])) {
            $data['attributes'] = [];
        }

        if (!isset($data['groups'])) {
            $data['groups'] = [];
        }

        $this->data = $data;
    }

    /**
     * Returns the raw data of a single field in the structure.
     *
     * @param string $fullName The fully qualified name of the field.
     * @return array|null
     */
    public function getField(string $fullName): array|null
    {
        return $this->findSingleItemOfList($this->data['attributes'], $fullName);
    }

    /**
     * Sets the raw data of a single field in the structure.
     *
     * If a change is detected, the structure will be marked as dirty.
     *
     * @param string $fullName The fully qualified name of the field.
     * @param array $field The raw data of the field.
     * @return void
     */
    public function setField(string $fullName, array $field): void
    {
        $this->setItemInList($this->data['attributes'], $fullName, $field);
    }

    /**
     * Removes a field from the structure.
     *
     * If the field does not exist, nothing will happen.
     * If the field is removed, the structure will be marked as dirty.
     *
     * @param string $fullName The fully qualified name of the field.
     * @return void
     */
    public function removeField(string $fullName): void
    {
        $this->setItemInList($this->data['attributes'], $fullName, null);
    }

    /**
     * Returns the raw data of all fields in the structure.
     * Can be filtered by client id and group.
     *
     * @param ConnectionConfig $config
     * @param bool|string|Stringable|null $clientId
     * @param false|string|Stringable|null $group
     * @return iterable
     */
    public function getFields(ConnectionConfig $config, bool|null|string|\Stringable $clientId = null, false|string|\Stringable|null $group = null): iterable
    {
        return $this->findItemsOfList(
            $this->data['attributes'],
            $config,
            $clientId,
            static function (array $item) use ($group) {
                if ($group === null) {
                    return true;
                }

                if ($group === false) {
                    return empty($item['group']);
                }

                return ($item['group'] ?? null) === (string)$group;
            }
        );
    }

    /**
     * Returns the raw data of a single group in the structure.
     *
     * @param string $fullName
     * @return array|null
     */
    public function getGroup(string $fullName): array|null
    {
        return $this->findSingleItemOfList($this->data['groups'], $fullName);
    }

    /**
     * Returns the raw data of all groups in the structure.
     * Can be filtered by client id.
     *
     * @param ConnectionConfig $config
     * @param bool|string|Stringable|null $clientId
     * @return iterable
     */
    public function getGroups(ConnectionConfig $config, bool|null|string|\Stringable $clientId = null): iterable
    {
        return $this->findItemsOfList($this->data['groups'], $config, $clientId);
    }

    /**
     * Sets the raw data of a single group in the structure.
     *
     * If a change is detected, the structure will be marked as dirty.
     *
     * @param string $fullName
     * @param array $group
     * @return void
     */
    public function setGroup(string $fullName, array $group): void
    {
        $this->setItemInList($this->data['groups'], $fullName, $group);
    }

    /**
     * Removes a group from the structure.
     *
     * If the group does not exist, nothing will happen.
     * If the group is removed, the structure will be marked as dirty, also all fields that reference the group will be updated.
     *
     * @param string $fullName
     * @return void
     */
    public function removeGroup(string $fullName): void
    {
        $this->setItemInList($this->data['groups'], $fullName, null);
    }

    /**
     * Returns true if the structure has been modified.
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->dirty;
    }

    /**
     * Marks the structure as clean (After saving changes).
     * @return void
     */
    public function markClean(): void
    {
        $this->dirty = false;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        $data = $this->data;

        // Ensure some fields are always passed as objects
        // Fields / attributes
        $objectFields = ['annotations', 'permissions', 'validations', 'required'];
        foreach ($data['attributes'] as &$field) {
            // Empty validators are always objects
            if (isset($field['validations'])) {
                foreach ($field['validations'] as $k => $validation) {
                    if (empty($validation)) {
                        $field['validations'][$k] = (object)$validation;
                    }
                }
                $field['validations'] = (object)$field['validations'];
            }

            foreach ($objectFields as $objectField) {
                if (isset($field[$objectField]) && !is_object($field[$objectField])) {
                    // Of course there is a special case for "required",
                    if ($objectField === 'required' && is_bool($field[$objectField])) {
                        // Keep booleans as they are
                        continue;
                    }

                    $field[$objectField] = (object)$field[$objectField];
                }
            }
        }
        unset($field);

        // Groups
        $objectFields = ['annotations'];
        foreach ($data['groups'] as &$group) {
            foreach ($objectFields as $objectField) {
                if (isset($group[$objectField]) && !is_object($group[$objectField])) {
                    $group[$objectField] = (object)$group[$objectField];
                }
            }
        }
        unset($group);
        $data['attributes'] = array_values($data['attributes']);
        $data['groups'] = array_values($data['groups']);

        return $data;
    }

    protected function findSingleItemOfList(array $list, string $fullName): array|null
    {
        foreach ($list as $item) {
            if (($item['name'] ?? null) === $fullName) {
                return $item;
            }
        }

        return null;
    }

    protected function findItemsOfList(array $list, ConnectionConfig $config, null|string|\Stringable|bool $clientId = null, callable|null $filter = null): iterable
    {
        $this->config = $config;
        foreach ($list as $item) {
            if ($clientId !== true && !$this->belongsTo($item['name'], $clientId)) {
                continue;
            }

            if ($filter !== null && !$filter($item)) {
                continue;
            }

            yield $item;
        }
    }

    protected function setItemInList(array &$list, string $fullName, array|null $item): void
    {
        foreach ($list as $k => $listItem) {
            if (($listItem['name'] ?? null) === $fullName) {
                // REMOVE
                if ($item === null) {
                    unset($list[$k]);
                    $this->dirty = true;
                    return;
                }

                // ADD/SET
                if (json_encode($item, JSON_THROW_ON_ERROR) !== json_encode($listItem, JSON_THROW_ON_ERROR)) {
                    $list[$k] = $item;
                    $this->dirty = true;
                }

                return;
            }
        }

        // Trying to remove an item that does not exist
        if ($item === null) {
            return;
        }

        $list[] = $item;
        $this->dirty = true;
    }
}
