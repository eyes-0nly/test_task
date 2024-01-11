<?php

namespace App\Service;

use Symfony\Component\Dotenv\Dotenv;

class AmoApiAuthConfigurator
{
    private array $credentials;

    private string $tokenPath;

    public function __construct()
    {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/../.env');

        $this->credentials = [ 
            'client_id' => getenv('INTEGRATION_ID'),
            'client_secret' => getenv('SECRET'),
            'redirect_uri' => getenv('REDIRECT_URI'),
            'base_domain' => getenv('BASE_DOMAIN'),
        ];
        
        $this->tokenPath = '../amo_token.json';
    }

    public function setCredentials(string $clientId, string $clientSecret, string $redirectUri, string $baseDomain): self 
    {
        $this->credentials = [ 
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'base_domain' => $baseDomain,
        ];

        return $this;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function setTokenPath(string $tokenPath): self
    {
        $this->tokenPath = $tokenPath;

        return $this;
    }

    public function getTokenPath(): string
    {
        return $this->tokenPath;
    }

}