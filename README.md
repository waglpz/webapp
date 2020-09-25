## Waglpz Web Application component

The Library enables you to work with web application as MVC or RESTful API or both.

### Requirements

PHP 7.4 or higher (see composer json)

### Installation

composer require waglpz/webapp

###### Example index.php

  ```php
      /* phpcs:disable */
      if (! \defined('APP_ENV')) {
          \define('APP_ENV', \getenv('APP_ENV') ?? 'dev');
      }
      /* phpcs:enable */
      
      require __DIR__ . '/vendor/autoload.php';
      
      use Aidphp\Http\ServerRequestFactory;
      use Waglpz\Webapp\App;
      
      $request = (new ServerRequestFactory())->createServerRequestFromGlobals();
      $config  = include \dirname(__DIR__) . '/config/main.php';
      $app     = new App($config);
      $app->run($request);
  ```

## Code Quality and Testing ##

To check for coding style violations, run

```
composer cs-check
```

To automatically fix (fixable) coding style violations, run

```
composer cs-fix
```

To check for static type violations, run

```
composer cs-fix
```

To check for regressions, run

```
composer test
```

To check all violations at once, run

```
composer check
```






