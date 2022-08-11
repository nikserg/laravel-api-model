# laravel-api-model

Allows to create pseudo-Eloquent models, which are fetched via API from remote server

## Installation

`composer require nikserg/laravel-api-model`

In `config/database.php` add

```php 
return [
    ...
    'connections' => [
        ...
        'api_fruits' => [
            'driver'  => 'api',
            'baseUri' => env('API_FRUITS_HOST', 'https://fruits.com/v1'),
            'verify'  => false,
        ],
    ...
    ],
    ...
];
```

## Usage

```php
use nikserg\LaravelApiModel\ApiModel;
class Banana extends ApiModel
{
    protected $connection = 'api_fruits';
    protected $table = 'bananas';
}
```

And then all Eloquent functions are available as usual.

### Authorization

If remote server requires some sort of HTTP-authorization (for ex. JWT-token), you need to pass 
`configuratorClass` parameter to config. This must be a FQDN of class implementing 
`\nikserg\LaravelApiModel\GuzzleConfigurator` interface. `modifyConfig` method of this class will be called
before first request, so it can modify array, which would be passed to `new \GuzzleHttp\Client($config)`.

Example of such config:
```php 
'api_fruits' => [
    'driver'       => 'api',
    'baseUri'      => 'baseUri' => env('API_FRUITS_HOST', 'https://fruits.com/v1'),
    'verify'       => false,
    'configurator' => \App\Models\GuzzleConfigurator::class,
],
```

Example of class (JWT-token authorization):

```php 
class GuzzleConfigurator implements \nikserg\LaravelApiModel\GuzzleConfigurator
{
    public static function modifyConfig(array $config): array
    {
        $user = auth()->user();
        $config['headers'] = [
            'Authorization' => 'Bearer ' . $user->getJwtToken(),
        ];
        return $config;
    }
}
```
