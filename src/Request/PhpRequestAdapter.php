<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Request;


class PhpRequestAdapter implements RequestAdapterInterface
{
    /**
     * @inheritDoc
     */
    public function getQueryValue(string $key): ?string
    {
        return $_GET[$key] ?? null;
    }
}
