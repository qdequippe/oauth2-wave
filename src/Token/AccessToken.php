<?php

namespace Qdequippe\OAuth2\Client\Token;

use League\OAuth2\Client\Token\AccessToken as BaseAccessToken;

class AccessToken extends BaseAccessToken
{
    /**
     * @var string|null
     */
    private $businessId;

    /**
     * @var string
     */
    private $scope;

    public function __construct(array $options = [])
    {
        if (!empty($options['businessId'])) {
            $this->businessId = $options['businessId'];
        }

        if (!empty($options['scope'])) {
            $this->scope = $options['scope'];
        }

        parent::__construct($options);
    }

    /**
     * @return string|null
     */
    public function getBusinessId()
    {
        return $this->businessId;
    }

    /**
     * @return string[]
     */
    public function getScopes()
    {
        if (empty($this->scope)) {
            return [];
        }

        return array_filter(explode(' ', $this->scope));
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function hasScope($scope)
    {
        $scope = trim(strtolower($scope));
        foreach ($this->getScopes() as $candidate) {
            if ($scope === strtolower(trim($candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $parameters = parent::jsonSerialize();

        if ($this->scope) {
            $parameters['scope'] = $this->scope;
        }

        if (!empty($this->businessId)) {
            $parameters['businessId'] = $this->businessId;
        }

        return $parameters;
    }
}