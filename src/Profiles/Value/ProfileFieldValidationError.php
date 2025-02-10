<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Profiles\Value;

readonly class ProfileFieldValidationError
{
    protected string $field;
    protected string $message;
    protected array $params;

    public function __construct(
        string $field,
        string $message,
        array  $params
    )
    {
        $this->field = $field;
        $this->message = $message;
        $this->params = $params;
    }

    /**
     * Returns the name of the field that has the validation error.
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Returns the error message.
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns additional parameters for the error message.
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
