<?php
declare(strict_types=1);

namespace Admin\Services;

class GoogleLoginService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/google.php';

        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->redirectUri = $config['redirect_uri'];
    }

    public function getLoginUrl(): string
    {
        $params = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'email profile',
            'access_type'   => 'online',
            // 'prompt'        => 'select_account', // Uitgeschakeld - gebruikt automatisch laatste account
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    // NIEUW: Wissel de code in voor gebruikersdata
    public function getGoogleUser(string $code): array
    {
        // 1. De Code inwisselen voor een Token (POST request)
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // SSL Verificatie (Soms nodig op lokale MAMP/WAMP)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $data = json_decode((string)$response, true);
        curl_close($ch);

        if (!isset($data['access_token'])) {
            throw new \Exception('Geen access token ontvangen van Google: ' . ($data['error_description'] ?? 'Onbekende fout'));
        }

        $accessToken = $data['access_token'];

        // 2. Met het Token de Gebruikersinfo ophalen (GET request)
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $accessToken;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $userResponse = curl_exec($ch);
        $userData = json_decode((string)$userResponse, true);
        curl_close($ch);

        if (!isset($userData['id'])) {
            throw new \Exception('Kon gebruikersdata niet ophalen.');
        }

        return $userData; // Hier zit o.a. id, email, name, picture in
    }
}