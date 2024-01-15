<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Dotenv\Dotenv;

class AmoApiAuthConfigurator
{
    private const TOKEN_PATH = '../amo_token.json';

    private array $credentials;

    private string $tokenPath;

    public function __construct()
    {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/../.env');

        $this->credentials = [ 
            'client_id' => getenv('INTEGRATION_ID'),
            'client_secret' => getenv('SECRET'),
            'redirect_uri' => getenv('REDIRECT_URI'),
            'base_domain' => getenv('BASE_DOMAIN'),
        ];
        
        $this->tokenPath = self::TOKEN_PATH;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getTokenPath(): string
    {
        return $this->tokenPath;
    }

}