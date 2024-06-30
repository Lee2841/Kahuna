<?php
namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\AccessToken;

class BaseController
{
    public static function checkToken(array $data): bool
    {
        if (!isset($data['api_user']) || !isset($data['api_token'])) {
            return false;
        }
        $token = new AccessToken($data['api_user'], $data['api_token']);
        return AccessToken::verify($token);
    }

    public static function getUserIdFromToken(array $data): ?int
    {
        if (!isset($data['api_token']) || !isset($data['api_user'])) {
            return null; // Return null if token or user ID is not provided
        }

        // Assuming user ID is passed as an integer
        return (int) $data['api_user'];
    }
    public static function sendResponse(mixed $data = null, int $code = 200, mixed $error = null): void
    {
        $response = [];

        http_response_code($code);

        if (!is_null($error) && !($code >= 200 && $code < 300)) {
            $response['error'] = [
                'message' => $error,
                'code' => $code
            ];
        } else {
            $response['data'] = $data;
        }

        header('Content-Type: application/json');

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}