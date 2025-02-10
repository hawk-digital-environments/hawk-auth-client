<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;

/**
 * @internal This class is not part of the public API and may change without notice.
 *
 * This class represents a value looking like this: ['foo', 'bar', 'baz'];
 * Which is stored on the {@see AbstractProfileElementData} as an indexed array.
 * The value MIGHT be missing, which will be treated as an empty array.
 */
class ListBasedValue
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
     * Returns true if the given value is in the list, stored in the given key.
     *
     * @param string $search The value to search for.
     * @param string $listKey The key where the list is stored.
     * @return bool
     */
    public function checkIfInList(string $search, string $listKey): bool
    {
        return in_array($search, $this->getList($listKey), true);
    }

    /**
     * Adds the given value to the list stored in the given key.
     * @param string $value The value to add.
     * @param string $listKey The key where the list is stored.
     * @return void
     */
    public function addToList(string $value, string $listKey): void
    {
        $list = $this->getList($listKey);

        if (in_array($value, $list, true)) {
            return;
        }

        $list[] = $value;
        $this->setList($list, $listKey);
    }

    /**
     * Removes the given value from the list stored in the given key.
     * @param string $value The value to remove.
     * @param string $listKey The key where the list is stored.
     * @return void
     */
    public function removeFromList(string $value, string $listKey): void
    {
        $list = $this->getList($listKey);

        if (!in_array($value, $list, true)) {
            return;
        }

        $this->setList(array_values(array_diff($list, [$value])), $listKey);
    }

    /**
     * Toggles the given value in the list stored in the given key.
     * @param bool $state Whether to add or remove the value.
     * @param string $value The value to toggle.
     * @param string $listKey The key where the list is stored.
     * @return void
     */
    public function toggleInList(bool $state, string $value, string $listKey): void
    {
        if ($state) {
            $this->addToList($value, $listKey);
        } else {
            $this->removeFromList($value, $listKey);
        }
    }

    /**
     * Returns the list stored in the given key.
     * @param string $listKey The key where the list is stored.
     * @return array
     */
    public function getList(string $listKey): array
    {
        $base = $this->getBaseValue()[$listKey] ?? [];
        return is_array($base) ? $base : [];
    }

    /**
     * Sets the list stored in the given key.
     * If the list is empty, the key will be removed.
     * Removing the last key will remove the whole value.
     *
     * @param array|null $list
     * @param string $listKey
     * @return void
     */
    public function setList(array|null $list, string $listKey): void
    {
        $base = $this->getBaseValue();

        if (empty($list)) {
            unset($base[$listKey]);
        } else {
            $base[$listKey] = array_values(array_unique($list));
        }

        $this->data->setAttr($this->baseKey, $base);
    }

    protected function getBaseValue(): array
    {
        $val = $this->data->getAttr($this->baseKey);
        return is_array($val) ? $val : [];
    }
}
