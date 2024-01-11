<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use Symfony\Component\Dotenv\Dotenv;

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

    public static function getDefaultCredentials(): array
    {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/../.env');
        return [
            'redirect_uri' => getenv('REDIRECT_URI'),
            'client_id' => getenv('INTEGRATION_ID'),
            'client_secret' => getenv('SECRET'),
            'base_domain' => getenv('BASE_DOMAIN')
        ];
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