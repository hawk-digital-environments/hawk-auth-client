<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Exception;


use Hawk\AuthClient\Profiles\Value\ProfileFieldValidationError;

class ProfileUpdateDataInvalidException extends ProfileUpdateFailedException
{
    readonly protected array $errors;

    public function __construct(
        string $message,
        \Throwable $previous,
        ProfileFieldValidationError ...$errors
    )
    {
        parent::__construct($message, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array|ProfileFieldValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
