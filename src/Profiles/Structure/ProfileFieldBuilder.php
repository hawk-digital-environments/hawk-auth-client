<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure;


use Hawk\AuthClient\Exception\GroupDoesNotExistException;
use Hawk\AuthClient\Profiles\Structure\Util\ListBasedValue;
use Hawk\AuthClient\Profiles\Structure\Util\ProfileFieldData;

class ProfileFieldBuilder extends ProfileField
{
    protected const int MAX_NUMBER = 9999999;

    protected ProfileStructureBuilder $structure;
    protected ListBasedValue $required;
    protected ListBasedValue $permissions;

    public function __construct(
        string                  $fullName,
        string                  $name,
        ProfileFieldData        $data,
        ProfileStructureBuilder $structure
    )
    {
        parent::__construct($fullName, $name, $data);
        $this->structure = $structure;
        $this->permissions = new ListBasedValue($data, 'permissions');
        $this->required = new ListBasedValue($data, 'required');
    }

    /**
     * Sets the display name for the attribute. Supports keys for localized values as well.
     * For example: ${profile.attribute.phoneNumber}. Hit the 'Globe Route' icon to add translations.
     *
     * @param string $name
     * @return $this
     */
    public function setDisplayName(string $name): static
    {
        $this->data->setAttr('displayName', $name);
        return $this;
    }

    /**
     * Specifies the user profile group where this attribute will be added.
     * This allows grouping various similar attributes together on different parts of the screen when creating or updating user.
     *
     * NOTE: The group MUST exist in the structure. If it does not, an exception will be thrown!
     *
     * @param string|\Stringable|null $group The group name or null to remove the group
     * @param false|string|\Stringable|null $clientId The client id or null to use the default client
     * @return $this
     * @see ProfileStructure::hasGroup() to check if a group exists
     * @see ProfileLayerInterface::define() to learn more about how you can access groups with multiple clients.
     */
    public function setGroup(string|\Stringable|null $group, false|null|string|\Stringable $clientId = null): static
    {
        if ($group === null) {
            $this->data->setAttr('group', null);
        } else if (!$this->structure->hasGroup($group, $clientId)) {
            throw new GroupDoesNotExistException((string)$group, $this->fullName);
        } else {
            $this->data->setAttr('group', $this->structure->getGroup($group, $clientId)->getFullName());
        }

        return $this;
    }

    /**
     * Sets this attribute to support multiple values. If this is true always expect an array of values.
     * @param bool $state
     * @return $this
     */
    public function setMultiValued(bool $state = true): static
    {
        $this->data->setAttr('multivalued', $state);
        return $this;
    }

    /**
     * Checks if this field is required to be filled when editing the profile as user.
     *
     * @return bool
     */
    public function isRequiredForUser(): bool
    {
        return $this->required->checkIfInList('user', 'roles');
    }

    /**
     * Sets this field to be required to be filled when editing the profile as user.
     * @param bool $state
     * @return $this
     */
    public function setRequiredForUser(bool $state = true): static
    {
        $this->required->toggleInList($state, 'user', 'roles');
        return $this;
    }

    /**
     * Checks if this field is required to be filled when editing the profile as admin.
     *
     * @return bool
     */
    public function isRequiredForAdmin(): bool
    {
        return $this->required->checkIfInList('admin', 'roles');
    }

    /**
     * Sets this field to be required to be filled when editing the profile as admin.
     * @param bool $state
     * @return $this
     */
    public function setRequiredForAdmin(bool $state = true): static
    {
        $this->required->toggleInList($state, 'admin', 'roles');
        return $this;
    }

    /**
     * Returns true if the field is required for either the user or the admin.
     * @see isRequiredForUser() To check if the field is required for the user.
     * @see isRequiredForAdmin() To check if the field is required for the admin.
     */
    #[\Override] public function isRequired(): bool
    {
        return $this->isRequiredForUser() || $this->isRequiredForAdmin();
    }

    /**
     * Checks if the user can view this field.
     *
     * @return bool
     */
    public function userCanView(): bool
    {
        return $this->permissions->checkIfInList('user', 'view');
    }

    /**
     * Sets the user to be able to view this field.
     * @param bool $state
     * @return $this
     */
    public function setUserCanView(bool $state = true): static
    {
        $this->permissions->toggleInList($state, 'user', 'view');
        return $this;
    }

    /**
     * Checks if the admin can view this field.
     *
     * @return bool
     */
    public function adminCanView(): bool
    {
        return $this->permissions->checkIfInList('admin', 'view');
    }

    /**
     * Sets the admin to be able to view this field.
     * @param bool $state
     * @return $this
     */
    public function setAdminCanView(bool $state = true): static
    {
        $this->permissions->toggleInList($state, 'admin', 'view');
        return $this;
    }

    /**
     * Checks if the user can edit this field.
     *
     * @return bool
     */
    public function userCanEdit(): bool
    {
        return $this->permissions->checkIfInList('user', 'edit');
    }

    /**
     * Sets the user to be able to edit this field.
     * @param bool $state
     * @return $this
     */
    public function setUserCanEdit(bool $state = true): static
    {
        $this->permissions->toggleInList($state, 'user', 'edit');
        return $this;
    }

    /**
     * Checks if the admin can edit this field.
     *
     * @return bool
     */
    public function adminCanEdit(): bool
    {
        return $this->permissions->checkIfInList('admin', 'edit');
    }

    /**
     * Sets the admin to be able to edit this field.
     * @param bool $state
     * @return $this
     */
    public function setAdminCanEdit(bool $state = true): static
    {
        $this->permissions->toggleInList($state, 'admin', 'edit');
        return $this;
    }

    /**
     * Checks if the field is read-only. This means that the field is not editable by the user or admin.
     * @return bool
     */
    #[\Override] public function isReadOnly(): bool
    {
        return !$this->userCanEdit() && !$this->adminCanEdit();
    }

    /**
     * Sets the input type for the field. This will determine how the field is displayed in the UI.
     * @param FieldInputTypes|string $inputType
     * @return $this
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#_changing-the-html-type-for-an-attribute
     */
    public function setInputType(FieldInputTypes|string $inputType): static
    {
        $this->annotations->setValue('inputType', is_string($inputType) ? $inputType : $inputType->value);
        return $this;
    }

    /**
     * Sets the helper text rendered before (above) the input field. Direct text or internationalization pattern (like ${i18n.key}) can be used here.
     * Text is NOT html escaped when rendered into the page, so you can use html tags here to format the text,
     * but you also have to correctly escape html control characters.
     *
     * @param string $helperText
     * @return $this
     */
    public function setHelperTextBefore(string $helperText): static
    {
        $this->annotations->setValue('inputHelperTextBefore', $helperText);
        return $this;
    }

    /**
     * Sets the helper text rendered after (under) the input field. Direct text or internationalization pattern (like ${i18n.key}) can be used here.
     * Text is NOT html escaped when rendered into the page, so you can use html tags here to format the text,
     * but you also have to correctly escape html control characters.
     *
     * @param string $helperText
     * @return $this
     */
    public function setHelperTextAfter(string $helperText): static
    {
        $this->annotations->setValue('inputHelperTextAfter', $helperText);
        return $this;
    }

    /**
     * Sets the HTML input placeholder attribute applied to the field - specifies a short hint that describes the expected value of an input field
     * (e.g. a sample value or a short description of the expected format). The short hint is displayed in the input field before the user
     * enters a value.
     *
     * @param string $placeholder
     * @return $this
     */
    public function setPlaceholder(string $placeholder): static
    {
        $this->annotations->setValue('inputTypePlaceholder', $placeholder);
        return $this;
    }

    /**
     * Sets a validator for the field. The validator key is the name of the validator and the value is the configuration for the validator.
     * Validators are part of the Keycloak API but can be extended with custom validators.
     *
     * NOTE: There are wrapper methods for the most common validators. Use them if possible.
     *
     * @param string $validatorKey
     * @param mixed $value This value MUST be json serializable! Can be null to remove the validator.
     * @return $this
     * @see https://www.keycloak.org/docs/latest/server_admin/index.html#built-in-validators to see which validators are build in by keycloak.
     */
    public function setValidator(string $validatorKey, mixed $value): static
    {
        $this->validations->setValue($validatorKey, $value);
        return $this;
    }

    /**
     * Check if the value is a double and within a lower and/or upper range. If no range is defined, the validator only checks whether the value is a valid number.
     *
     * @param float|null $min an integer to define the lower range.
     * @param float|null $max an integer to define the upper range.
     * @return $this
     */
    public function setDoubleValidator(float|null $min = null, float|null $max = null): static
    {
        return $this->setValidator('double', [
            'min' => (string)($min ?? -self::MAX_NUMBER),
            'max' => (string)($max ?? self::MAX_NUMBER)
        ]);
    }

    /**
     * Check if the value is an integer and within a lower and/or upper range. If no range is defined, the validator only checks whether the value is a valid number.
     * @param int|null $min an integer to define the lower range.
     * @param int|null $max an integer to define the upper range.
     * @return $this
     */
    public function setIntegerValidator(int|null $min = null, int|null $max = null): static
    {
        return $this->setValidator('integer', [
            'min' => (string)($min ?? -self::MAX_NUMBER),
            'max' => (string)($max ?? self::MAX_NUMBER)
        ]);
    }

    /**
     * Check if the value has a valid e-mail format.
     * @param int|null $maxLocalLength an integer to define the maximum length for the local part of the email. It defaults to 64 per specification.
     * @return $this
     */
    public function setEmailValidator(int|null $maxLocalLength = null): static
    {
        return $this->setValidator('email', [
            'max-local-length' => max(1, $maxLocalLength ?? 64)
        ]);
    }

    /**
     * Check if the value has a valid format based on ISO 8601. This validator can be used with inputs using the html5-date input type.
     * @return $this
     */
    public function setDateValidator(): static
    {
        return $this->setValidator('iso-date', []);
    }

    /**
     * Check if the number of values is within a lower and/or upper range. If no range is defined, the validator checks if there are 0 - 1 values.
     *
     * @param int|null $min
     * @param int|null $max
     * @return $this
     */
    public function setMultiValueValidator(int|null $min = null, int|null $max = null): static
    {
        return $this->setValidator('multi-value', [
            'min' => (string)(max(0, $min ?? 0)),
            'max' => (string)(max(0, $max ?? 1))
        ]);
    }

    /**
     * Check if the value is from the defined set of allowed values. Useful to validate values entered through select and multiselect fields.
     * @param array $options array of strings containing allowed values.
     * @return $this
     */
    public function setOptionsValidator(array $options): static
    {
        return $this->setValidator('options', [
            'options' => $options
        ]);
    }

    /**
     * Check if the value matches a specific RegEx pattern.
     * @param string $pattern the RegEx pattern to use when validating values.
     * @return $this
     */
    public function setPatternValidator(string $pattern): static
    {
        return $this->setValidator('pattern', [
            'pattern' => $pattern,
            'error-message' => ''
        ]);
    }

    /**
     * Sets ANY attribute of the fields data-structure.
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
