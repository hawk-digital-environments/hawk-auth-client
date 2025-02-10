<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;


use Hawk\AuthClient\Layers\ProfileLayerInterface;
use Hawk\AuthClient\Profiles\Structure\Util\AbstractProfileElementData;
use Hawk\AuthClient\Profiles\Structure\Util\AssocListBasedValue;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileGroupData;

class ProfileGroup implements \Stringable, \JsonSerializable
{
    protected string $fullName;
    protected string $name;
    protected AbstractProfileElementData $data;
    protected AssocListBasedValue $annotations;

    public function __construct(
        string           $fullName,
        string           $name,
        ProfileGroupData $data
    )
    {
        $this->fullName = $fullName;
        $this->name = $name;
        $this->data = $data;
        $this->annotations = new AssocListBasedValue($data, 'annotations');
    }

    /**
     * Returns a unique name for the group. This name will be used to reference the group when binding an attribute to a group.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the full name of the group. This name is unique across all clients.
     *
     * @return string
     * @see ProfileLayerInterface::define() to learn more about how the full name is generated.
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Returns a user-friendly name for the group that should be used when rendering a group of attributes in user-facing forms.
     * Supports keys for localized values as well. For example: ${profile.attribute.group.address}.
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->data->getAttr('displayHeader') ?? $this->name;
    }

    /**
     * Returns a text that should be used as a tooltip when rendering user-facing forms.
     * @return string
     */
    public function getDisplayDescription(): string
    {
        return $this->data->getAttr('displayDescription') ?? '';
    }

    /**
     * Returns ANY attribute of the groups data-structure.
     *
     * This method is designed to support you if this api does not implement a specific method for the attribute you are looking for.
     * BE CAREFUL, there might be breaking changes in the future if you rely on this method.
     *
     * @param string $attrKey
     * @return mixed
     * @see https://www.keycloak.org/docs-api/latest/rest-api/index.html#UPGroup
     */
    public function getRawAttribute(string $attrKey): mixed
    {
        return $this->data->getAttr($attrKey);
    }

    /**
     * Returns the value of the given annotation key. If the annotation does not exist, null is returned.
     * Annotations are used to store additional information about the group that is not part of the standard data structure.
     * @param string $annotationKey
     * @return mixed
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_defining-ui-annotations
     */
    public function getAnnotation(string $annotationKey): mixed
    {
        return $this->annotations->getValue($annotationKey);
    }

    /**
     * Returns all annotations for this group.
     * @return array
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_defining-ui-annotations
     */
    public function getAnnotations(): array
    {
        return $this->annotations->getBaseValue();
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->fullName;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array|null
    {
        return $this->data->jsonSerialize();
    }
}
