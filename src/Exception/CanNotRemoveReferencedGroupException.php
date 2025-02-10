<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


class CanNotRemoveReferencedGroupException extends AbstractProfileStructureException
{
    public function __construct(
        string $groupFullName,
        string $requiringFieldFullName
    )
    {
        parent::__construct(
            sprintf(
                'Can not remove group "%s" because it is referenced by field "%s"',
                $groupFullName,
                $requiringFieldFullName
            )
        );
    }
}
