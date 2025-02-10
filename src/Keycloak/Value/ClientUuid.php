<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Value;


use Hawk\AuthClient\Util\Validator;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class ClientUuid implements \Stringable
{
    protected string $uuid;

    public function __construct(string $uuid)
    {
        Validator::requireUuid($uuid);
        $this->uuid = $uuid;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->uuid;
    }
}
