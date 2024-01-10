<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;

class AmoApiAuthDirector
{
    private AmoApiAuthBuilder $builder;

    private string $tokenPath;

    public function __construct(array $apiClientConfig)
    {
        $builder = new AmoApiAuthBuilder($apiClientConfig);
        $this->builder = $builder;
    }

    public static function getDefaultCredentials(): array
    {
        return [
            'redirect_uri' => $_ENV['REDIRECT_URI'],
            'client_id' => $_ENV['INTEGRATION_ID'],
            'client_secret' => $_ENV['SECRET'],
            'base_domain' => $_ENV['BASE_DOMAIN']
        ];
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