<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Value;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class CacheBuster implements \Stringable
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
