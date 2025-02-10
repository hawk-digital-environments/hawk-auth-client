<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;


use Hawk\AuthClient\Exception\CanNotRemoveReferencedGroupException;
use Hawk\AuthClient\Keycloak\KeycloakApiClient;
use Hawk\AuthClient\Keycloak\Value\ConnectionConfig;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileStructureData;

class ProfileStructureBuilder extends ProfileStructure
{
    protected const array DEFAULT_FIELD = [
        'name' => '',
        'displayName' => '',
        'validations' => [],
        'annotations' => [],
        'permissions' => [
            'view' => ['admin', 'user'],
            'edit' => ['admin', 'user']
        ],
        'multivalued' => false
    ];

    protected const array DEFAULT_GROUP = [
        'name' => '',
        'displayHeader' => '',
        'displayDescription' => '',
        'annotations' => []
    ];

    /**
     * @inheritdoc
     */
    protected bool $isBuilder = true;
    protected KeycloakApiClient $apiClient;

    public function __construct(
        ConnectionConfig     $config,
        ProfileStructureData $data,
        KeycloakApiClient    $apiClient
    )
    {
        parent::__construct($config, $data);
        $this->apiClient = $apiClient;
    }

    /**
     * @inheritDoc
     * @return ProfileGroupBuilder
     */
    #[\Override] public function getGroup(\Stringable|string $name, false|\Stringable|string|null $clientId = null): ProfileGroupBuilder
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::getGroup($name, $clientId);
    }

    /**
     * Removes a field group from the structure.
     * This will fail if there are fields referencing this group.
     *
     * @param string|\Stringable $name The name of the group to remove.
     * @param false|string|\Stringable|null $container The client id to use as prefix.
     * @return $this
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function removeGroup(string|\Stringable $name, false|null|string|\Stringable $container = null): static
    {
        $fullName = $this->getFullName($name, $container);

        // Fail if there are fields referencing this group.
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->data->getFields($this->config, true) as $field) {
            throw new CanNotRemoveReferencedGroupException($fullName, $field['name']);
        }

        $this->data->removeGroup($fullName);
        unset($this->groups[$fullName]);

        return $this;
    }

    /**
     * @inheritDoc
     * @return ProfileFieldBuilder
     */
    #[\Override] public function getField(\Stringable|string $name, false|\Stringable|string|null $clientId = null): ProfileFieldBuilder
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::getField($name, $clientId);
    }

    /**
     * Removes a field from the structure.
     *
     * @param string|\Stringable $name The name of the field to remove.
     * @param false|string|\Stringable|null $container The client id to use as prefix.
     * @return $this
     * @see ProfileLayerInterface::define() to learn more on how the client namespacing is used in the structure
     */
    public function removeField(string|\Stringable $name, false|null|string|\Stringable $container = null): static
    {
        $fullName = $this->getFullName($name, $container);
        $this->data->removeField($fullName);
        unset($this->fields[$fullName]);
        return $this;
    }

    /**
     * Persists the modified structure to the Keycloak server.
     * If you have made changes to the structure, you should call this method to save them.
     * WARNING: Please re-fetch user/profile/structure data from their respective repositories after calling this method.
     *
     * @return void
     */
    public function save(): void
    {
        if ($this->data->isDirty()) {
            $this->apiClient->updateProfileStructure($this->data);
            $this->data->markClean();
        }
    }

    #[\Override] protected function makeFieldObject(
        string $fullName,
        string $name
    ): ProfileField|null
    {
        if ($this->data->getField($fullName) === null) {
            // If only checking, we don't want to create a new field.
            if ($this->isChecking) {
                return null;
            }

            $this->data->setField(
                $fullName,
                array_merge(
                    self::DEFAULT_FIELD,
                    [
                        'name' => $fullName,
                        'displayName' => '${' . $fullName . '}'
                    ]
                )
            );
        }

        return new ProfileFieldBuilder(
            $fullName,
            $name,
            new ProfileFieldData($this->data, $fullName),
            $this
        );
    }

    #[\Override] protected function makeGroupObject(
        string $fullName,
        string $name
    ): ProfileGroup|null
    {
        if ($this->data->getGroup($fullName) === null) {
            // If only checking, we don't want to create a new group.
            if ($this->isChecking) {
                return null;
            }

            $this->data->setGroup(
                $fullName,
                array_merge(
                    self::DEFAULT_GROUP,
                    [
                        'name' => $fullName,
                        'displayHeader' => '${' . $fullName . '}'
                    ]
                )
            );
        }

        return new ProfileGroupBuilder(
            $fullName,
            $name,
            new ProfileGroupData($this->data, $fullName)
        );
    }
}
