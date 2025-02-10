<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Layers;


use Hawk\AuthClient\Permissions\Guard;
use Hawk\AuthClient\Users\Value\User;

interface GuardLayerInterface
{
    /**
     * Normally you can infer the {@see Guard} instance form your authentication layer.
     * However, sometimes you might want to access a {@see Guard} instance for a user that is not currently authenticated.
     * In this case, you can use this method to get a {@see Guard} instance for the given user.
     * @param string|\Stringable|User $user Either the {@see User} instance or the user ID to get the guard for.
     * @return Guard
     */
    public function getOne(string|\Stringable|User $user): Guard;
}
