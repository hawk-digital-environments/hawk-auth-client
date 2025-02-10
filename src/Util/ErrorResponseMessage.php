<?php
declare(strict_types=1);


namespace Hawk\AuthClient\Util;


use Psr\Http\Message\ResponseInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class ErrorResponseMessage implements \Stringable
{
    protected string $message;

    public function __construct(ResponseInterface $response)
    {
        $this->message = 'HTTP ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        $content = $response->getBody()->getContents();

        if (json_validate($content)) {
            $data = json_decode($content, true);
            if (isset($data['error'])) {
                $this->message = $data['error'];
            }
            if (isset($data['error_description'])) {
                $this->message .= ': ' . $data['error_description'];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->message;
    }
}
