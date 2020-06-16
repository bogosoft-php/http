## bogosoft/http

This library contains various implementations of PSR-7 and PSR-15 contracts.

Class|Description
-----|-----------
`DeferredStream`|A partial implementation (abstract class) of the `Psr\Http\Message\StreamInterface` contract that defers stream access until it is absolutley necessary.
`DelegatedMiddleware`|An implementation of the `Psr\Http\Server\MiddlewareInterface` that delegates HTTP request processing to a `callable`.
`DelegatedRequestHandle`|An implementation of the `Psr\Http\Server\RequestHandlerInterface` that delegates HTTP request handling to a `callable`.
`IStreamCopyable`|An interface that defines a `copyTo` method.
`Stream`|An implementation of the `Psr\Http\Message\StreamInterface` contract that wraps a `resource`.

#### Requirements

- PHP 7.4+

#### Installation

```bash
composer require bogosoft/http
```
