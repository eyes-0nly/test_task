<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;

class AmoApiAuthDirector
{
    private AmoApiAuthBuilder $builder;

    private string $tokenPath;

    public function __construct(array $apiClientConfig, string $tokenPath)
    {
        $builder = new AmoApiAuthBuilder($apiClientConfig);
        $this->builder = $builder;
        $this->tokenPath = $tokenPath;
    }

    public function buildAuthentication(): self
    {
        $this->builder->init();

        return $this;
    }

    public function getAuthenticatedClient(): AmoCRMApiClient
    {
        return $this->builder->getAuthClient($this->tokenPath);
    }
}