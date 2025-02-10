<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;

/**
 * @internal This class is not part of the public API and may change without notice.
 */
abstract class AbstractProfileElementData implements \JsonSerializable
{
    protected readonly ProfileStructureData $data;
    protected readonly string $fullName;

    final public function __construct(
        ProfileStructureData $data,
        string               $fullName
    )
    {
        $this->data = $data;
        $this->fullName = $fullName;
    }

    /**
     * Returns the attribute value of the given key. If the key does not exist, null is returned.
     * @param string $key
     * @return mixed
     */
    public function getAttr(string $key): mixed
    {
        return $this->getAttrs($this->fullName)[$key] ?? null;
    }

    /**
     * Sets the attribute value of the given key. If the value is null, the key will be removed.
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setAttr(string $key, mixed $value): static
    {
        $item = $this->getAttrs($this->fullName);

        if ($value === null) {
            if ($item === null || !isset($item[$key])) {
                return $this;
            }

            unset($item[$key]);
        } else {
            if ($item === null) {
                $item = [];
            }

            $item[$key] = $value;
        }

        $this->setAttrs($this->fullName, $item);

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array|null
    {
        return $this->getAttrs($this->fullName);
    }

    /**
     * MUST be implemented by the extending class to access the actual attributes.
     * @param string $fullName
     * @return array|null
     */
    abstract protected function getAttrs(string $fullName): array|null;

    /**
     * MUST be implemented by the extending class to set the actual attributes.
     * @param string $fullName
     * @param array $attrs
     * @return void
     */
    abstract protected function setAttrs(string $fullName, array $attrs): void;
}
