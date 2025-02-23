<?php
declare(strict_types=1);


namespace Hawk\AuthClient\FrontendApi\Handler;


use Hawk\AuthClient\FrontendApi\Util\HandlerContext;
use Hawk\AuthClient\FrontendApi\Util\Request;
use Hawk\AuthClient\FrontendApi\Util\ResponseFactory;

interface FrontendApiHandlerInterface
{
    /**
     * Determines if this handler can handle the given request.
     * If this method returns true, the handle method will be called.
     * The handlers are asked for `canHandle` in the order they were added to the handler chain.
     * The first handler that returns true will handle the request.
     *
     * @param Request $request The request to handle
     * @return bool
     */
    public function canHandle(Request $request): bool;

    /**
     * Handles the request.
     *
     * @param Request $request The request to handle
     * @param ResponseFactory $responseFactory The response factory to use
     * @param HandlerContext $context The context to use
     * @return mixed The result of the response factory "build" methods.
     */
    public function handle(Request $request, ResponseFactory $responseFactory, HandlerContext $context): mixed;
}
