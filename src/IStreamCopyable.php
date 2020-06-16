<?php

namespace Bogosoft\Http;

/**
 * Represents a strategy for copying the contents of an object to a target
 * resource.
 *
 * @package Bogosoft\Http
 */
interface IStreamCopyable
{
    /**
     * Copy the contents of the current object to a target resource.
     *
     * @param  resource  $target A target resource to which the contents of the
     *                           current object will be copied.
     * @return int|false         The number of bytes copied to the target
     *                           resource, or {@see false} on failure to copy.
     */
    function copyTo($target);
}
