<?php

declare(strict_types=1);

namespace Bogosoft\Http;

use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IServerRequest;
use Psr\Http\Server\RequestHandlerInterface as IRequestHandler;

/**
 * An implementation of the {@see IRequestHandler} contract that delegates
 * request handling to a {@see callable} object.
 *
 * The delegate is expected to be of the form:
 *
 * - fn({@see IServerRequest}): {@see IResponse}
 *
 * This class cannot be inherited.
 *
 * @package Bogosoft\Http
 */
final class DelegatedRequestHandler implements IRequestHandler
{
    /** @var callable */
    private $delegate;

    /**
     * Create a new delegated request handler.
     *
     * @param callable $delegate An invokable object responsible for
     *                           converting an HTTP request into an HTTP
     *                           response.
     */
    function __construct(callable $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    function handle(IServerRequest $request): IResponse
    {
        return ($this->delegate)($request);
    }
}
