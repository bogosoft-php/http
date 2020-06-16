<?php

declare(strict_types=1);

namespace Bogosoft\Http;

use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IServerRequest;
use Psr\Http\Server\MiddlewareInterface as IMiddleware;
use Psr\Http\Server\RequestHandlerInterface as IRequestHandler;

/**
 * An implementation of the {@see IMiddleware} contract that delegates HTTP
 * request processing to a {@see callable} object.
 *
 * The delegate is expected to be of the form:
 *
 * - fn({@see IServerRequest}, {@see IRequestHandler}): {@see IResponse}
 *
 * This class cannot be inherited.
 *
 * @package Bogosoft\Http
 */
final class DelegatedMiddleware implements IMiddleware
{
    /** @var callable */
    private $delegate;

    /**
     * Create a new delegated middleware component.
     *
     * @param callable $delegate An invokable object responsible for HTTP
     *                           request processing.
     */
    function __construct(callable $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function process(IServerRequest $request, IRequestHandler $handler): IResponse
    {
        return ($this->delegate)($request, $handler);
    }
}
