<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;

/**
 * @internal This class is not part of the public API and may change without notice.
 *
 * This class represents a value looking like this: ['key' => 'value', 'key2' => 'value2', ...]
 * Which is stored on the {@see AbstractProfileElementData} as an associative array.
 * The value MIGHT be missing, which will be treated as an empty array.
 */
class AssocListBasedValue
{
    protected AbstractProfileElementData $data;
    protected string $baseKey;

    public function __construct(
        AbstractProfileElementData $data,
        string                     $baseKey
    )
    {
        $this->data = $data;
        $this->baseKey = $baseKey;
    }

    /**
     * Returns the value of the given key. If the key does not exist, null is returned.
     * @param string $key
     * @return mixed
     */
    public function getValue(string $key): mixed
    {
        return $this->getBaseValue()[$key] ?? null;
    }

    /**
     * Sets the value of the given key. If the value is null, the key will be removed.
     * Removing the last key will remove the whole value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string $key, mixed $value): void
    {
        $base = $this->getBaseValue();

        if ($value === null) {
            unset($base[$key]);
        } else {
            $base[$key] = $value;
            ksort($base);
        }

        $this->data->setAttr($this->baseKey, empty($base) ? null : $base);
    }

    /**
     * Returns the base value as an associative array.
     * @return array
     */
    public function getBaseValue(): array
    {
        $val = $this->data->getAttr($this->baseKey);
        return is_array($val) ? $val : [];
    }
}
