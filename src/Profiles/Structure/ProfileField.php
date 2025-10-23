<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;

use Hawk\AuthClient\Profiles\Structure\Util\AbstractProfileElementData;
use Hawk\AuthClient\Profiles\Structure\Util\AssocListBasedValue;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;

class ProfileField implements \Stringable, \JsonSerializable
{
    protected string $fullName;
    protected string $name;
    protected AssocListBasedValue $validators;
    protected AbstractProfileElementData $data;
    protected AssocListBasedValue $annotations;

    public function __construct(
        string           $fullName,
        string           $name,
        ProfileFieldData $data,
    )
    {
        $this->fullName = $fullName;
        $this->name = $name;
        $this->data = $data;
        $this->annotations = new AssocListBasedValue($data, 'annotations');
        $this->validators = new AssocListBasedValue($data, 'validations');
    }

    /**
     * Returns the name of the field, used to uniquely identify a field.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the full name of the field. This name is unique across all clients.
     *
     * @return string
     * @see ProfileLayerInterface::define() to learn more about how the full name is generated.
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Display name for the attribute. Supports keys for localized values as well.
     * For example: ${profile.attribute.phoneNumber}. Hit the 'Globe Route' icon to add translations.
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->data->getAttr('displayName');
    }

    /**
     * Specifies the user profile group where this attribute will be added.
     * This allows grouping various similar attributes together on different parts of the screen when creating or updating user.
     *
     * @return string|null
     * @see ProfileStructure::getGroup() to get a group by its name.
     */
    public function getGroup(): string|null
    {
        return $this->data->getAttr('group');
    }

    /**
     * Checks if this attribute supports multiple values. If this is true always expect an array of values.
     *
     * @return bool
     */
    public function isMultiValued(): bool
    {
        return $this->data->getAttr('multivalued') ?? false;
    }

    /**
     * Returns true if the field is required. This means that the field must be filled out by the user.
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->data->getAttr('required') ?? false;
    }

    /**
     * Returns true if the field is read-only. This means that the field is not editable by the user.
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->data->getAttr('readOnly') ?? false;
    }

    /**
     * Returns the defined type of the input field.
     * @return string
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_changing-the-html-type-for-an-attribute
     */
    public function getInputType(): string
    {
        return $this->annotations->getValue('inputType') ?? FieldInputTypes::TEXT->value;
    }

    /**
     * Helper text rendered before (above) the input field. Direct text or internationalization pattern (like ${i18n.key}) can be used here.
     * Text is NOT html escaped when rendered into the page, so you can use html tags here to format the text,
     * but you also have to correctly escape html control characters.
     *
     * @return string
     */
    public function getHelperTextBefore(): string
    {
        return $this->annotations->getValue('inputHelperTextBefore');
    }

    /**
     * Helper text rendered after (under) the input field. Direct text or internationalization pattern (like ${i18n.key}) can be used here.
     * Text is NOT html escaped when rendered into the page, so you can use html tags here to format the text,
     * but you also have to correctly escape html control characters.
     *
     * @return string|null
     */
    public function getHelperTextAfter(): string|null
    {
        return $this->annotations->getValue('inputHelperTextAfter');
    }

    /**
     * HTML input placeholder attribute applied to the field - specifies a short hint that describes the expected value of an input field
     * (e.g. a sample value or a short description of the expected format). The short hint is displayed in the input field before the user
     * enters a value.
     *
     * @return string|null
     */
    public function getPlaceholder(): string|null
    {
        return $this->annotations->getValue('inputTypePlaceholder');
    }

    /**
     * Returns the configuration of a single validator for this field.
     * Validators mostly rely on the keycloak validators, but can be extended by custom validators.
     * @param string $validatorKey
     * @return mixed
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#built-in-validators to see which validators are build in by keycloak.
     */
    public function getValidation(string $validatorKey): mixed
    {
        return $this->validators->getValue($validatorKey);
    }

    /**
     * Returns all validators for this field.
     * @return array
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#built-in-validators to see which validators are build in by keycloak.
     */
    public function getValidations(): array
    {
        return $this->validators->getBaseValue();
    }

    /**
     * Returns ANY attribute of the fields data-structure.
     *
     * This method is designed to support you if this api does not implement a specific method for the attribute you are looking for.
     * BE CAREFUL, there might be breaking changes in the future if you rely on this method.
     *
     * @param string $attrKey
     * @return mixed
     * @see https://www.keycloak.org/docs-api/latest/rest-api/index.html#_defining-ui-annotations
     */
    public function getRawAttribute(string $attrKey): mixed
    {
        return $this->data->getAttr($attrKey);
    }

    /**
     * Returns the value of the given annotation key. If the annotation does not exist, null is returned.
     * Annotations are used to store additional information about the field that is not part of the standard data structure.
     * @param string $annotationKey
     * @return mixed
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#built-in-annotations to see which annotation are build in by keycloak.
     */
    public function getAnnotation(string $annotationKey): mixed
    {
        return $this->annotations->getValue($annotationKey);
    }

    /**
     * Returns all annotations for this field.
     * @return array
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#built-in-annotations to see which annotation are build in by keycloak.
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
    #[\Override] public function jsonSerialize(): array
    {
        return $this->data->jsonSerialize();
    }
}
