<?php
declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Exceptions\AmoCRMApiException;

class AmoApiAuthBuilder
{
    private AmoCRMApiClient $apiClient;

    public function init(): void 
    {
        $credentials = $this->getCredentials();
        $this->apiClient = (new AmoCRMApiClient($credentials['clientId'], $credentials['clientSecret'], $credentials['redirectUri']))
        ->setAccountBaseDomain($credentials['baseDomain']);
    }

    public function getCredentials(): array
    {
        return [
            'redirectUri' => $_ENV['REDIRECT_URI'],
            'clientId' => $_ENV['INTEGRATION_ID'],
            'clientSecret' => $_ENV['SECRET'],
            'baseDomain' => $_ENV['BASE_DOMAIN']
        ];
    }

    public function getAccessTokenFromJsonFile(string $tokenPath): AccessToken 
    {
        $authToken = $_ENV['AUTH_TOKEN'];

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

        $this->apiClient->account()->getCurrent();

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
                exit('Auth token has been revoked. Set new one in .env file.');
            }
        }
    }
}