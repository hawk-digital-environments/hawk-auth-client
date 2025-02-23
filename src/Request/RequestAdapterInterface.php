<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Request;


interface RequestAdapterInterface
{
    /**
     * MUST return the value of the query parameter with the given key.
     * If the parameter is not set, MUST return null.
     * If the parameter is numeric, it MUST be converted to a string.
     * If the parameter is an array, it MUST return the first value.
     *
     * @param string $key The key of the query parameter.
     * @return string|null
     */
    public function getQueryValue(string $key): ?string;

    /**
     * MUST return the value of the post parameter, if it was sent as an urlencoded form.
     * It does not expect to work with JSON or other formats.
     * If the request is either not POST or the parameter is not set, MUST return null.
     *
     * @param string $key The key of the post parameter.
     * @return mixed
     */
    public function getPostValue(string $key): mixed;

    /**
     * MUST return the value of the header with the given key.
     * Only the last value of the header is expected to be returned.
     * If the header is not set, MUST return null.
     *
     * @param string $key The key of the header.
     * @return string|null
     */
    public function getHeaderValue(string $key): ?string;
}
