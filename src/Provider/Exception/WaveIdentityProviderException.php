<?php

namespace Qdequippe\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class WaveIdentityProviderException extends IdentityProviderException
{
    public static function clientException(ResponseInterface $response, $data)
    {
        $message = '';
        $code = $response->getStatusCode();
        $body = (string) $response->getBody();

        if (isset($data['errors'][0]['message'])) {
            $message = $data['errors'][0]['message'];
        }

        return new static($message, $code, $body);
    }
}