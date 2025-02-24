<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Tests\Auth;


use Hawk\AuthClient\Auth\KeycloakProvider;
use Hawk\AuthClient\Auth\StatefulAuth;
use Hawk\AuthClient\Auth\StatefulUserTokenStorage;
use Hawk\AuthClient\Permissions\GuardFactory;
use Hawk\AuthClient\Request\RequestAdapterInterface;
use Hawk\AuthClient\Session\SessionAdapterInterface;
use Hawk\AuthClient\Users\UserStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(StatefulAuth::class)]
class StatefulAuthTest extends TestCase
{
    public function testItConstructs(): void
    {
        $sut = new StatefulAuth(
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(StatefulUserTokenStorage::class),
            $this->createStub(SessionAdapterInterface::class),
            $this->createStub(KeycloakProvider::class),
            $this->createStub(UserStorage::class),
            $this->createStub(GuardFactory::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->assertInstanceOf(StatefulAuth::class, $sut);
    }

    public function testItSetsTheRedirectHandlerOnConstruction(): void
    {
        $sut = new class(
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(StatefulUserTokenStorage::class),
            $this->createStub(SessionAdapterInterface::class),
            $this->createStub(KeycloakProvider::class),
            $this->createStub(UserStorage::class),
            $this->createStub(GuardFactory::class),
            $this->createStub(LoggerInterface::class)
        ) extends StatefulAuth {
            public function getRedirectHandler(): mixed
            {
                return $this->redirectHandler;
            }
        };

        $this->assertInstanceOf(\Closure::class, $sut->getRedirectHandler());
    }

    public function testItCanSetTheRedirectHandler(): void
    {
        $handler = function () {

        };

        $sut = new class(
            $this->createStub(RequestAdapterInterface::class),
            $this->createStub(StatefulUserTokenStorage::class),
            $this->createStub(SessionAdapterInterface::class),
            $this->createStub(KeycloakProvider::class),
            $this->createStub(UserStorage::class),
            $this->createStub(GuardFactory::class),
            $this->createStub(LoggerInterface::class)
        ) extends StatefulAuth {
            public function getRedirectHandler(): mixed
            {
                return $this->redirectHandler;
            }
        };

        $sut->setRedirectHandler($handler);
        $this->assertSame($handler, $sut->getRedirectHandler());
    }

}
