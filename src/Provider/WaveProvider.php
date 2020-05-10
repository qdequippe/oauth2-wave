<?php

namespace Qdequippe\OAuth2\Client\Provider;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Qdequippe\OAuth2\Client\Token\AccessToken as WaveAccessToken;

class WaveProvider extends AbstractProvider
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
        if (isset($data['errors']) && $response->getStatusCode() >= 400) {
            $message = $data['errors'][0]['message'];
            $code = $data['errors'][0]['extensions']['code'];

            throw new IdentityProviderException($message, $code, $data);
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
}