# Wave Provider for OAuth 2.0 Client

This package provides [Wave](https://www.waveapps.com) OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

[![Build Status](https://travis-ci.org/qdequippe/oauth2-wave.svg)](http://travis-ci.org/qdequippe/oauth2-wave)

## Installation

To install, use composer:

```
composer require qdequippe/oauth2-wave
```

## Usage

Usage is the same as The League's OAuth client, using `\Qdequippe\OAuth2\Client\Provider\Wave` as the provider.

### Authorization Code Flow

```php
$provider = new Qdequippe\OAuth2\Client\Provider\Wave([
    'clientId'          => '{wave-client-id}',
    'clientSecret'      => '{wave-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getNickname());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Wave authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['invoice:read', 'user:read'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```

Find scopes available here https://developer.waveapps.com/hc/en-us/articles/360032818132-OAuth-Scopes.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/qdequippe/oauth2-wave/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Quentin Dequippe](https://github.com/qdequippe)
- [All Contributors](https://github.com/qdequippe/oauth2-wave/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/qdequippe/oauth2-wave/blob/master/LICENSE) for more information.