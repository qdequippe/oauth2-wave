<?php

namespace Qdequippe\OAuth2\Client\Provider;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Qdequippe\OAuth2\Client\Provider\Exception\WaveIdentityProviderException;
use Qdequippe\OAuth2\Client\Token\AccessToken as WaveAccessToken;

class Wave extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'userId';

    protected $api = 'https://api.waveapps.com/oauth2';

    public function getBaseAuthorizationUrl()
    {
        return $this->api . '/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->api . '/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://gql.waveapps.com/graphql/public';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['errors']) && $response->getStatusCode() >= 400) {
            throw WaveIdentityProviderException::clientException($response, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new WaveResourceOwner($response);
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        // GraphQL query
        $query = 'query { user { id firstName lastName defaultEmail } }';

        $options = [
            'body' => json_encode(['query' => $query]),
            'headers' => [
                'content-type' => 'application/json',
            ],
        ];
        $request = $this->getAuthenticatedRequest(self::METHOD_POST, $url, $token, $options);

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }

    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new WaveAccessToken($response);
    }

    protected function getAccessTokenMethod()
    {
        return self::METHOD_GET;
    }
}
