<?php

namespace Qdequippe\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class WaveResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * @var array
     */
    private $data;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->data = $response['data']['user'];
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getValueByKey($this->data, 'id');
    }

    public function getEmailAddress()
    {
        return $this->getValueByKey($this->data, 'defaultEmail');
    }

    public function getFirstName()
    {
        return $this->getValueByKey($this->data, 'firstName');
    }

    public function getLastName()
    {
        return $this->getValueByKey($this->data, 'lastName');
    }

    public function toArray()
    {
        return $this->data;
    }
}
