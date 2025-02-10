<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;


use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Layers\ProfileLayerInterface;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfilePrefixTrait;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;

class ProfileStructure implements \JsonSerializable
{
    use ProfilePrefixTrait;

    protected ProfileStructureData $data;

    protected array $fields = [];
    protected array $groups = [];

    /**
     * Allows the {@see ProfileStructureBuilder} to check if the existence is currently checked -> no need to create the object.
     * @var bool
     */
    protected bool $isChecking = false;

    /**
     * {@see ProfileStructureBuilder} sets this to true, this allows us to recheck every time if an object exists instead of caching "null" values.
     * @var bool
     */
    protected bool $isBuilder = false;

    public function __construct(
        ConnectionConfig     $config,
        ProfileStructureData $data
    )
    {
        $this->config = $config;
        $this->data = $data;
    }

    /**
     * Returns a list of {@see ProfileGroup} objects of all groups known to the structure.
     *
     * @param false|string|\Stringable|null $clientId Allows you to filter the groups by client id.
     *                                                default (null) returns all groups explicitly defined for this client (e.g. hawk.client-id.group-name)
     *                                                false returns all "global" groups (e.g. group-name, user-metadata)
     *                                                string|\Stringable returns all groups for the given client id
     *                                                true returns ALL groups, regardless of client id
     * @return iterable<ProfileGroup>
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function getGroups(bool|null|string|\Stringable $clientId = null): iterable
    {
        yield from $this->mapListToObjects(
            $this->data->getGroups($this->config, $clientId),
            $this->groups,
            [$this, 'makeGroupObject']
        );
    }

    /**
     * Checks if a group with the given name exists in the structure.
     * @param string|\Stringable $name The name of the group to check for.
     * @param false|string|\Stringable|null $clientId Allows you to filter the groups by client id.
     * @return bool
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function hasGroup(string|\Stringable $name, false|null|string|\Stringable $clientId = null): bool
    {
        return $this->hasOne(
            $this->getFullName($name, $clientId),
            $this->groups,
            [$this, 'makeGroupObject']
        );
    }

    /**
     * Returns a {@see ProfileGroup} object for the given group name.
     * @param string|\Stringable $name The name of the group to retrieve.
     * @param false|string|\Stringable|null $clientId Allows you to filter the groups by client id.
     * @return ProfileGroup|null
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function getGroup(string|\Stringable $name, false|null|string|\Stringable $clientId = null): ProfileGroup|null
    {
        return $this->mapOneToObject(
            $this->getFullName($name, $clientId),
            $this->groups,
            [$this, 'makeGroupObject']
        );
    }

    /**
     * Returns a list of {@see ProfileField} objects of all fields known to the structure.
     *
     * @param false|string|\Stringable|null $clientId Allows you to filter the fields by client id.
     *                                                default (null) returns all fields explicitly defined for this client (e.g. hawk.client-id.field-name)
     *                                                false returns all "global" fields (e.g. username, email, firstName)
     *                                                string|\Stringable returns all fields for the given client id
     *                                                true returns ALL fields, regardless of client id
     * @param string|\Stringable|null $group Allows you to filter the fields by group.
     * @return iterable<ProfileField>
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function getFields(bool|null|string|\Stringable $clientId = null, string|\Stringable|null $group = null): iterable
    {
        yield from $this->mapListToObjects(
            $this->data->getFields(
                $this->config,
                $clientId,
                $group === null ? null : $this->getFullName($group, $clientId)
            ),
            $this->fields,
            [$this, 'makeFieldObject']
        );
    }

    /**
     * Checks if a field with the given name exists in the structure.
     * @param string|\Stringable $name The name of the field to check for.
     * @param false|string|\Stringable|null $clientId Allows you to filter the fields by client id.
     * @return bool
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function hasField(string|\Stringable $name, false|null|string|\Stringable $clientId = null): bool
    {
        return $this->hasOne(
            $this->getFullName($name, $clientId),
            $this->fields,
            [$this, 'makeFieldObject']
        );
    }

    /**
     * Returns a {@see ProfileField} object for the given field name.
     * @param string|\Stringable $name The name of the field to retrieve.
     * @param false|string|\Stringable|null $clientId Allows you to filter the fields by client id.
     * @return ProfileField|null
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function getField(string|\Stringable $name, false|null|string|\Stringable $clientId = null): ProfileField|null
    {
        return $this->mapOneToObject(
            $this->getFullName($name, $clientId),
            $this->fields,
            [$this, 'makeFieldObject']
        );
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return $this->data->jsonSerialize();
    }

    /**
     * @param iterable $list
     * @param array $cache
     * @param callable $mapper
     * @return iterable<ProfileField|ProfileGroup>
     */
    protected function mapListToObjects(iterable $list, array &$cache, callable $mapper): iterable
    {
        foreach ($list as $row) {
            $object = $this->mapOneToObject($row['name'], $cache, $mapper);

            // @codeCoverageIgnoreStart
            // This should theoretically never happen, but we want to be safe
            if ($object === null) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            yield $object;
        }
    }

    protected function mapOneToObject(
        string   $fullName,
        array    &$cache,
        callable $mapper
    ): ProfileField|ProfileGroup|null
    {
        if (array_key_exists($fullName, $cache)) {
            if (!$this->isBuilder || $cache[$fullName] !== null) {
                return $cache[$fullName];
            }
        }

        return $cache[$fullName] = $mapper($fullName, $this->getLocalName($fullName));
    }

    protected function hasOne(string $fullName, array &$cache, callable $mapper): bool
    {
        if (array_key_exists($fullName, $cache)) {
            return $cache[$fullName] !== null;
        }

        try {
            $this->isChecking = true;
            return $this->mapOneToObject($fullName, $cache, $mapper) !== null;
        } finally {
            $this->isChecking = false;
        }
    }

    protected function makeFieldObject(
        string $fullName,
        string $name
    ): ProfileField|null
    {
        return $this->data->getField($fullName) === null
            ? null
            : new ProfileField(
                $fullName,
                $name,
                new ProfileFieldData($this->data, $fullName)
            );
    }

    protected function makeGroupObject(
        string $fullName,
        string $name
    ): ProfileGroup|null
    {
        return $this->data->getGroup($fullName) === null
            ? null
            : new ProfileGroup(
                $fullName,
                $name,
                new ProfileGroupData($this->data, $fullName)
            );
    }
}
