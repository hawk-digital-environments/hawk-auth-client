<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class GroupDoesNotExistException extends AbstractProfileStructureException
{
    public function __construct(
        string $groupFullName,
        string $fieldToAttachFullName
    )
    {
        parent::__construct(
            sprintf(
                'Group "%s" does not exist. Can not attach it to field "%s"',
                $groupFullName,
                $fieldToAttachFullName
            )
        );
    }
}
