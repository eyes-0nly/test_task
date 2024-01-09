<?php

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;

class AmoApiAuthDirector
{
    private AmoApiAuthBuilder $builder;

    private string $tokenPath;

    public function __construct(AmoApiAuthBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function setTokenPath(string $tokenPath): AmoApiAuthDirector
    {
        $this->tokenPath = $tokenPath;
        return $this;
    }

    public function buildAuthentication(): AmoApiAuthDirector
    {
        $this->builder->init();
        return $this;
    }

    public function getAuthenticatedClient(): AmoCRMApiClient
    {
        return $this->builder->getAuthClient($this->tokenPath);
    }
}