# Smartmessages.net web API PHP client class

<a href="https://info.smartmessages.net/"><img src="https://www.smartmessages.net/img/smartmessages-logo.svg" width="250" height="28" alt="Smartmessages email marketing"></a>

This contains a PHP client library and example code for the [smartmessages.net](https://info.smartmessages.net/) email management service's web API.

Please feel free to suggest modifications, submit tickets and pull requests in [our GitHub repo](https://github.com/Smartmessages/PHPClient) – these libraries are intended to make *your* life easier!

You can (and should) add this to your project using composer:

```
composer require smartmessages/phpclient=~3.0
```

## Installation
To install the library and its dependencies, run `composer install`, then load the autoloader with `require 'vendor/autoload.php';`.

Note that because this is a library, it doesn't have a `composer.lock` file, in accordance with composer's guidelines, and will use whatever shared dependencies you might have, which is essentially only guzzle.

## Version History
Version 3.0 updated to Guzzle 7.0, and PHP 8.0+.

Version 2.0 was rewritten to use [Guzzle](http://docs.guzzlephp.org/en/latest/) as its HTTP client, providing faster, more robust processing and [PSR-7](http://www.php-fig.org/psr/psr-7/) compatibility with many frameworks.

## API Documentation
Complete documentation for the API can be found in [our help pages](https://wiki.smartmessages.net/#API)

See the accompanying LICENSE file for terms of use (MIT).
