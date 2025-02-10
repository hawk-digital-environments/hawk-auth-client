<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Users\UserFactory;

class MissingUserFactoryCollaboratorException extends \DomainException implements AuthClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('The collaborator "userFactory" must be set to an instance of: '. UserFactory::class);
    }
}
