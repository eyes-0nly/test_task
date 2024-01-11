<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Exceptions\AmoCRMApiException;
use Symfony\Component\Dotenv\Dotenv;

class AmoApiAuthBuilder
{
    private AmoCRMApiClient $apiClient;

    private array $apiClientConfig;

    public function __construct(array $apiClientConfig)
    {
        $this->apiClientConfig = $apiClientConfig;
    }

    public function init(): void 
    {
        $this->apiClient = (new AmoCRMApiClient($this->apiClientConfig['client_id'], $this->apiClientConfig['client_secret'], $this->apiClientConfig['redirect_uri']))
        ->setAccountBaseDomain($this->apiClientConfig['base_domain']);
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

    public function getAccessTokenFromJsonFile(string $tokenPath): AccessToken 
    {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/../.env');
        $authToken =  getenv('AUTH_TOKEN');

        if (file_exists($tokenPath)) {
            $rawToken = json_decode(file_get_contents($tokenPath), true);
            $accessToken = new AccessToken($rawToken);
        } else {
            $accessToken = $this->getAccessAndRefreshToken($tokenPath, $authToken);
        }

        if ($accessToken->hasExpired()) {
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);
            $this->saveAccessTokenToJsonFile($accessToken, $tokenPath);
        }

        return $accessToken;

    }

    public function saveAccessTokenToJsonFile(AccessToken $accessToken, string $tokenPath): void 
    {
        file_put_contents($tokenPath, json_encode($accessToken->jsonSerialize(), JSON_PRETTY_PRINT));
    }

    public function getAuthClient(string $tokenPath): AmoCRMApiClient
    {
        $accessToken = $this->getAccessTokenFromJsonFile($tokenPath);

        $this->apiClient->setAccessToken($accessToken);

        return $this->apiClient;
    }

    //нужно если ключ авторизации устарел 
    public function getAccessAndRefreshToken(string $tokenPath, string $authToken) {

        try {
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($authToken);
            $this->saveAccessTokenToJsonFile($accessToken, $tokenPath);

            return $accessToken;
        } catch (AmoCRMApiException $e) {
            if ($e->getErrorCode() === 400) {
                echo('Auth token has been revoked. Set new one in .env file.');
            }
        }
    }
}