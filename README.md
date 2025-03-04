# Hades

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Github Actions][ico-gh-actions]][link-gh-actions]
[![Codecov][ico-codecov]][link-codecov]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

Dealing with errors when building an API can be a pain. Instead of manually building error responses you can simply throw an exception and the Hades will handle the response for you.

## Installation

You may use Composer to install this package into your Laravel project:

``` bash
$ composer require jenky/hades
```

## Configuration

### Generic Error Response Format

By default all thrown exceptions will be transformed to the following format:

```js
{
    'message' => ':message', // The exception message
    'status' => ':status_code', // The corresponding HTTP status code, default to 500
    'errors' => ':errors', // The error bag, typically validation error messages
    'code' => ':code', // The exception code
    'debug' => ':debug', // The debug information
}
```

> The debug information only available when application is not in `production` environment and `debug` mode is on.

Example:

```bash
curl --location --request GET 'http://myapp.test/api/user' \
--header 'Accept: application/json'
```

```js
{
  "message": "Unauthenticated.",
  "type": "AuthenticationException",
  "status": 401,
  "code": 0,
}
```

> Any keys that aren't replaced with corresponding values will be removed from the final response.

## Formatting Exception

If you would like to use different error format for your application, you should call the `Hades::errorFormat()` method in the `boot` method of your `App\Providers\AppServiceProvider` class:

```php
use Jenky\Hades\Hades;

/**
 * Bootstrap any application services.
 *
 * @return void
 */
public function boot()
{
    Hades::errorFormat([
        'message' => ':message',
        'error_description' => ':error',
    ]);
}
```

### Customizing Exception Response

Hades uses [`api-error`](https://github.com/jenky/api-error) internally. Please see the [exception transformations](https://github.com/jenky/api-error?tab=readme-ov-file#exception-transformations) to see how it works.

To add a custom transformers, you can use the config file

```php
// configs/hades.php

return [

    'transformers' => [
        App\Exceptions\Transformers\MyCustomTransformer::class,
    ],

];
```

Alternatively, if Laravel can't inject your custom exception transformer, you may wish to register the exception transformer with the `api_error.exception_transformer` tag in your service provider. Typically, you should call this method from the `register` method of your application's `App\Providers\AppServiceProvider` class:

```php
use App\Exceptions\Transformers\MyCustomTransformer;
use Illuminate\Contracts\Debug\ExceptionHandler;

public function register(): void
{
    $this->app->bind(MyCustomTransformer::class, static fn () => 'your binding logic');

    $this->app->tag('api_error.exception_transformer', MyCustomTransformer::class);
}
```

## Content Negotiation

### Forcing the JSON Response

By default, Laravel expects the request should contains header `Accept` with the MIME type `application/json` or custom MIME with `json` format such as `application/vnd.myapp.v1+json` in order to return JSON response. Otherwise your may get redirected to login page if the credentials are invalid or missing/passing invalid authorization token.

While this is a good design practice, sometimes you may wish to attach the header to request automatically, such as using Laravel as pure API backend. To do this, you should call the `Hades::forceJsonOutput()` method within the `boot` method of your `App\Providers\AppServiceProvider`.

```php
use Jenky\Hades\Hades;

public function boot(): void
{
    Hades::forceJsonOutput();
}
```

Hades will add the header `Accept: application/json` to all [incoming API requests](#identify-api-requests). If you want to use custom MIME type, you may use the `withMimeType` to specify the MIME type:

```php
Hades::forceJsonOutput()
    ->withMimeType('application/vnd.myapp.v1+json');
```

### Identify API Requests

In order to force the response to return JSON output, Hades needs to identify the incoming request so it doesn't add the `Accept` header on your normal HTML pages.

By default, all your API routes defined in `routes/api.php` have `/api` URI prefix automatically applied. Hades will inspects the incoming request URI and determines it's URI matches the `/api` prefix.

To customize this behavior, you may pass the closure to `Hades::forceJsonOutput()` to instruct Hades how to identify the incoming request:

```php
use Illuminate\Http\Request;

Hades::forceJsonOutput(static function (Request $request) {
    return $request->is('api/v1/*');
});
```

If your application is built solely for API use, you can configure the request to always return a JSON response.

```php
use Illuminate\Http\Request;

Hades::forceJsonOutput(static fn () => true);
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email contact@lynh.me instead of using the issue tracker.

## Credits

- [Lynh][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jenky/hades.svg?logo=packagist&style=for-the-badge
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge
[ico-travis]: https://img.shields.io/travis/jenky/hades/master.svg?style=for-the-badge
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jenky/hades.svg?style=for-the-badge
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jenky/hades.svg?style=for-the-badge
[ico-downloads]: https://img.shields.io/packagist/dt/jenky/hades.svg?style=for-the-badge
[ico-gh-actions]: https://img.shields.io/github/actions/workflow/status/jenky/hades/testing.yml?branch=master&label=actions&logo=github&style=for-the-badge
[ico-codecov]: https://img.shields.io/codecov/c/github/jenky/hades?logo=codecov&style=for-the-badge

[link-packagist]: https://packagist.org/packages/jenky/hades
[link-travis]: https://travis-ci.org/jenky/hades
[link-scrutinizer]: https://scrutinizer-ci.com/g/jenky/hades/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jenky/hades
[link-downloads]: https://packagist.org/packages/jenky/hades
[link-author]: https://github.com/jenky
[link-contributors]: ../../contributors
[link-gh-actions]: https://github.com/jenky/hades/actions
[link-codecov]: https://codecov.io/gh/jenky/hades
