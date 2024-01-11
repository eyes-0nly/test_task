<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use App\Service\AmoApiAuthBuilder;
use App\Service\AmoApiAuthConfigurator;

class AmoApiAuthDirector
{
    private AmoApiAuthBuilder $builder;

    private string $tokenPath;

    public function __construct(AmoApiAuthConfigurator $config)
    {
        $this->builder = new AmoApiAuthBuilder($config);
        $this->tokenPath = ($this->builder->getApiClientConfig())->getTokenPath();
    }

    public function buildAuthentication(AmoApiAuthConfigurator $config = null): self
    {
        $this->builder->init();

        return $this;
    }

    public function getAuthenticatedClient(): AmoCRMApiClient
    {
        return $this->builder->getAuthClient($this->tokenPath);
    }
}