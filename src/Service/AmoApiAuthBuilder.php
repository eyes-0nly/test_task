<?php

declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Exceptions\AmoCRMApiException;
use Symfony\Component\Dotenv\Dotenv;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class AmoApiAuthBuilder
{
    private AmoCRMApiClient $apiClient;

    private AmoApiAuthConfigurator $apiClientConfig;
    
    private LoggerInterface $logger;

    public function __construct(AmoApiAuthConfigurator $apiClientConfig, LoggerInterface $logger)
    {
        $this->apiClientConfig = $apiClientConfig;

        $this->logger = $logger;
    }

    public function init(): void 
    {
        $credentials = $this->apiClientConfig->getCredentials();
        $this->apiClient = (new AmoCRMApiClient(
                $credentials['client_id'],
                $credentials['client_secret'],
                $credentials['redirect_uri']
            ))
                ->setAccountBaseDomain($credentials['base_domain']);
    }

    public function getApiClientConfig() {
        return $this->apiClientConfig;
    }

    public function getAccessTokenFromJsonFile(string $tokenPath): AccessToken 
    {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/../.env');
        $authToken = getenv('AUTH_TOKEN') ?? '';

        if (file_exists($tokenPath)) {
            $rawToken = json_decode(file_get_contents($tokenPath), true);
            $accessToken = new AccessToken($rawToken);
        } else {
            try {
                $accessToken = $this->getAccessAndRefreshToken($tokenPath, $authToken);
            } catch (AmoCRMApiException $e) {
                $this->logger->error('Auth token has been revoked.');
            }
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

        $this->apiClient
            ->getOAuthClient()
            ->setAccessTokenRefreshCallback(
                function (AccessToken $accessToken, string $tokenPath) {
                    $this->saveAccessTokenToJsonFile($accessToken, $tokenPath);
                }
            );
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
            if ($e->getErrorCode() === Response::HTTP_BAD_REQUEST) {
                throw $e;
            }
        }
    }
}