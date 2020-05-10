<?php

namespace Qdequippe\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use PHPUnit\Framework\TestCase;
use Qdequippe\OAuth2\Client\Provider\Exception\WaveIdentityProviderException;
use Qdequippe\OAuth2\Client\Provider\WaveProvider;
use Mockery as m;
use Qdequippe\OAuth2\Client\Provider\WaveResourceOwner;
use Qdequippe\OAuth2\Client\Token\AccessToken as WaveAccessToken;

class WaveProviderTest extends TestCase
{
    use QueryBuilderTrait;

    protected $provider;

    public function setUp(): void
    {
        $this->provider = new WaveProvider([
            'clientId'      => 'mock_client_id',
            'clientSecret'  => 'mock_secret',
            'redirectUri'   => 'none',
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertEquals('/oauth2/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/oauth2/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        // lifted from DigitalOcean docs
        $testResponse = [
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
            'expires_in' => 2592000,
            'refresh_token' => '00a3aae641658d',
            'scope' => 'invoice:read business:write',
            'userId' => '78d5f5e875e5d1e5f5ef5zdez5frg',
            'businessId' => 'EJIEef55E48F48E'
        ];
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(\json_encode($testResponse));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        /** @var WaveAccessToken $token */
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals($testResponse['access_token'], $token->getToken());
        $this->assertEquals(time() + $testResponse['expires_in'], $token->getExpires());
        $this->assertEquals($testResponse['refresh_token'], $token->getRefreshToken());
        $this->assertTrue($token->hasScope('invoice:read'));
        $this->assertTrue($token->hasScope('business:write'));
        $this->assertFalse($token->hasScope('badscope'));
        $this->assertEquals($testResponse['userId'], $token->getResourceOwnerId());
        $this->assertEquals($testResponse['businessId'], $token->getBusinessId());
    }

    public function testUserData()
    {
        $response_data = [
            'data' => [
                'user' => [
                    'id' => uniqid(),
                    'defaultEmail' => 'johndoe@example.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ]
            ]
        ];

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "token_type":"bearer"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);
        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(json_encode($response_data));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        /** @var WaveResourceOwner $user */
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($response_data['data']['user']['id'], $user->getId());
        $this->assertEquals($response_data['data']['user']['id'], $user->toArray()['id']);
        $this->assertEquals($response_data['data']['user']['defaultEmail'], $user->getEmailAddress());
        $this->assertEquals($response_data['data']['user']['defaultEmail'], $user->toArray()['defaultEmail']);
        $this->assertEquals($response_data['data']['user']['firstName'], $user->getFirstName());
        $this->assertEquals($response_data['data']['user']['firstName'], $user->toArray()['firstName']);
        $this->assertEquals($response_data['data']['user']['lastName'], $user->getLastName());
        $this->assertEquals($response_data['data']['user']['lastName'], $user->toArray()['lastName']);
    }

    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $this->expectException(WaveIdentityProviderException::class);
        $message = uniqid();
        $status = rand(400, 600);
        $code = uniqid();
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"errors": [{"message": "'.$message.'", "extensions": { "code": "'.$code.'" }}]}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}