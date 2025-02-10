<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Request;


interface RequestAdapterInterface
{
    /**
     * MUST return the value of the query parameter with the given key.
     * If the parameter is not set, MUST return null.
     *
     * @param string $key The key of the query parameter.
     * @return string|null
     */
    public function getQueryValue(string $key): ?string;
}
