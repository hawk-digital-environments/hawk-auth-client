<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Util;


use Hawk\AuthClient\FrontendApi\FrontendApi;
use Hawk\AuthClient\FrontendApi\Handler\FrontendApiHandlerInterface;
use Hawk\AuthClient\Request\RequestAdapterInterface;

/**
 * @internal This class is not part of the public API and may change at any time.
 */
class HandlerStack
{
    /**
     * @var FrontendApiHandlerInterface[]
     */
    protected array $handlers = [];
    protected bool $fallthroughAllowed = false;

    public function addHandler(FrontendApiHandlerInterface $handler): void
    {
        $this->handlers[get_class($handler)] = $handler;
    }

    public function setFallthroughAllowed(bool $state): void
    {
        $this->fallthroughAllowed = $state;
    }

    public function handle(
        RequestAdapterInterface $request,
        ResponseFactory         $responseFactory,
        HandlerContext          $context
    ): mixed
    {
        $route = $request->getQueryValue(FrontendApi::API_ROUTE_QUERY_PARAMETER);
        if (empty($route)) {
            if ($this->fallthroughAllowed) {
                return null;
            }

            return $responseFactory->buildBadRequest('Your request is missing the required "' . FrontendApi::API_ROUTE_QUERY_PARAMETER . '" query parameter');
        }

        $noneMatch = $request->getHeaderValue('If-None-Match');
        /** @noinspection HashTimingAttacksInspection */
        if (!empty($noneMatch) && $noneMatch === md5((string)$context->getCacheBuster())) {
            return $responseFactory->buildNotModified();
        }

        $frontendRequest = new Request($route, $request);

        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($frontendRequest)) {
                return $handler->handle($frontendRequest, $responseFactory, $context);
            }
        }

        return $responseFactory->buildNotFound('Route not found');
    }
}
