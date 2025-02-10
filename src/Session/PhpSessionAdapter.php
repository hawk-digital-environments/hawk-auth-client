<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Session;


use Hawk\AuthClient\Exception\SessionIsNotStartedException;

class PhpSessionAdapter implements SessionAdapterInterface
{
    public const string SESSION_NAMESPACE = 'hawk_auth_client';
    protected bool $checkSession;

    public function __construct(bool|null $checkSession = null)
    {
        $this->checkSession = $checkSession ?? true;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $this->checkIfSessionIsStarted();
        return $_SESSION[self::SESSION_NAMESPACE][$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): void
    {
        $this->checkIfSessionIsStarted();
        $_SESSION[self::SESSION_NAMESPACE][$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $this->checkIfSessionIsStarted();
        return isset($_SESSION[self::SESSION_NAMESPACE][$key]);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): void
    {
        $this->checkIfSessionIsStarted();
        unset($_SESSION[self::SESSION_NAMESPACE][$key]);
    }

    protected function checkIfSessionIsStarted(): void
    {
        if ($this->checkSession && session_status() === PHP_SESSION_NONE) {
            throw new SessionIsNotStartedException();
        }

        if (!isset($_SESSION[self::SESSION_NAMESPACE])) {
            $_SESSION[self::SESSION_NAMESPACE] = [];
        }
    }
}
