<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Keycloak\Value;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
readonly class ApiToken implements \JsonSerializable, \Stringable
{
    protected string $token;
    protected \DateTimeInterface $expiresAt;

    public function __construct(
        string             $token,
        \DateTimeInterface $expiresAt
    )
    {
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Returns true if the token is expired before the given date.
     * @param \DateTimeInterface $now
     * @return bool
     */
    public function isExpired(\DateTimeInterface $now): bool
    {
        return $this->expiresAt <= $now;
    }

    /**
     * Returns the date when the token expires.
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * @inheritDoc
     */
    #[\Override] public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'expiresAt' => $this->expiresAt->format(\DateTimeInterface::ATOM)
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->token;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['token'],
            new \DateTimeImmutable($data['expiresAt'])
        );
    }
}
