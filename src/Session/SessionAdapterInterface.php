<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Session;


interface SessionAdapterInterface
{
    /**
     * MUST return the value of the key from the session. If the key does not exist, MUST return null.
     * @param string $key The key to get from the session
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * MUST set the value of the key in the session. If the key already exists, MUST overwrite the value.
     * @param string $key The key to set in the session
     * @param mixed $value The value to set in the session
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * MUST return true if the key exists in the session, otherwise MUST return false.
     * @param string $key The key to check in the session
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * MUST remove the key from the session.
     * @param string $key The key to remove from the session
     * @return void
     */
    public function remove(string $key): void;
}
