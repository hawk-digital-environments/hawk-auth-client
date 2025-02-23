<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Request;


class PhpRequestAdapter implements RequestAdapterInterface
{
    /**
     * @inheritDoc
     */
    #[\Override] public function getQueryValue(string $key): ?string
    {
        if (!isset($_GET[$key])) {
            return null;
        }
        
        if (is_array($_GET[$key])) {
            return (string)reset($_GET[$key]);
        }

        return (string)$_GET[$key];
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getPostValue(string $key): mixed
    {
        return $_POST[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function getHeaderValue(string $key): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$serverKey] ?? null;
    }
}
