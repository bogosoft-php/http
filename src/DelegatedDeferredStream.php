<?php

declare(strict_types=1);

namespace Bogosoft\Http;

/**
 * An implementation of the {@see DeferredStream} abstract class that
 * delegates the copying of data to a target resource or stream to a
 * {@see callable} object.
 *
 * The delegate is expected to be of the form:
 *
 * - fn({@see resource}): {@see int}|{@see false}
 *
 * This class cannot be inherited.
 *
 * @package Bogosoft\Http
 */
final class DelegatedDeferredStream extends DeferredStream
{
    /** @var callable */
    private $delegate;

    /**
     * Create a new delegated, deferred stream.
     *
     * @param callable $delegate An invokable object responsible for copying
     *                           data to a target resource or stream.
     */
    function __construct(callable $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    protected function copyToInternal($target)
    {
        return ($this->delegate)($target);
    }
}
