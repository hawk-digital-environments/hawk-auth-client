<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Structure\Util;

/**
 * @internal This class is not part of the public API and may change without notice.
 */
class ProfileGroupData extends AbstractProfileElementData
{
    /**
     * @inheritDoc
     */
    #[\Override] protected function getAttrs(string $fullName): array|null
    {
        return $this->data->getGroup($fullName);
    }

    /**
     * @inheritDoc
     */
    #[\Override] protected function setAttrs(string $fullName, array $attrs): void
    {
        $this->data->setGroup($fullName, $attrs);
    }
}
