<?php

declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use App\Service\AmoApiAuthBuilder;
use App\Service\AmoApiAuthConfigurator;
use Psr\Log\LoggerInterface;

class AmoApiAuthDirector
{
    private AmoApiAuthBuilder $builder;

    private string $tokenPath;

    public function __construct(AmoApiAuthConfigurator $config, LoggerInterface $logger)
    {
        $this->builder = new AmoApiAuthBuilder($config, $logger);
        $this->tokenPath = ($this->builder->getApiClientConfig())->getTokenPath();
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