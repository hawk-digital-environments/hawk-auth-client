<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;


class ProfileGroupBuilder extends ProfileGroup
{
    /**
     * Sets a user-friendly name for the group that should be used when rendering a group of attributes in user-facing forms.
     * Supports keys for localized values as well. For example: ${profile.attribute.group.address}.
     *
     * @param string $name
     * @return $this
     */
    public function setDisplayName(string $name): static
    {
        $this->data->setAttr('displayHeader', $name);
        return $this;
    }

    /**
     * Sets a text that should be used as a tooltip when rendering user-facing forms.
     * @param string $description
     * @return $this
     */
    public function setDisplayDescription(string $description): static
    {
        $this->data->setAttr('displayDescription', $description);
        return $this;
    }

    /**
     * Sets ANY attribute of the groups data-structure.
     *
     * This method is designed to support you if this api does not implement a specific method for the attribute you are looking for.
     * BE CAREFUL, there might be breaking changes in the future if you rely on this method.
     *
     * @param string $attrKey
     * @param mixed $value
     * @return $this
     * @see https://www.keycloak.org/docs-api/latest/rest-api/index.html#UPGroup
     */
    public function setRawAttribute(string $attrKey, mixed $value): static
    {
        $this->data->setAttr($attrKey, $value);
        return $this;
    }

    /**
     * Sets the value of the given annotation key.
     * Annotations are used to store additional information about the group that is not part of the standard data structure.
     *
     * @param string $annotationKey The key of the annotation
     * @param mixed $value This value MUST be json serializable!
     * @return $this
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_defining-ui-annotations
     */
    public function setAnnotation(string $annotationKey, mixed $value): static
    {
        $this->annotations->setValue($annotationKey, $value);
        return $this;
    }
}
