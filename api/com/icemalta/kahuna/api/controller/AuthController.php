<?php

namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\{User, AccessToken};

class AuthController extends BaseController
{
    public static function login(array $params, array $data): void
    {
        try {
            $email = $data['email'];
            $password = $data['password'];
            $user = User::authenticate($email, $password);
            if ($user) {
                $token = new AccessToken($user->getId());
                $token = AccessToken::save($token);
                // Check if the user is an agent
                if ($user->getRole() === 'agent') {
                    // If the user is an agent, send a flag indicating admin status along with token
                    self::sendResponse(['user' => $user->getId(), 'isAdmin' => true, 'token' => $token->getToken()]);
                } else {
                    // If the user is not an agent, send a flag indicating non-admin status along with token
                    self::sendResponse(['user' => $user->getId(), 'isAdmin' => false, 'token' => $token->getToken()]);
                }
            } else {
                self::sendResponse(null, 401, 'Login failed.');
            }
        } catch (\Exception $e) {
            // Handle any exceptions by sending a 500 error
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }


    public static function logout(array $params, array $data): void
    {
        try {
            // Check token and proceed
            if (self::checkToken($data)) {
                $userId = $data['api_user'];
                $token = new AccessToken(userId: $userId);
                $token = AccessToken::delete($token);
                self::sendResponse(data: ['message' => 'You have been logged out.']);
            } else {
                self::sendResponse(code: 403, error: 'Missing, invalid or expired token.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function verifyToken(array $params, array $data): void
    {
        try {
            // Check token and proceed
            if (self::checkToken($data)) {
                self::sendResponse(data: ['valid' => true, 'token' => $data['api_token']]);
            } else {
                self::sendResponse(code: 403, error: 'Missing, invalid or expired token.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function connectionTest(array $params, array $data): void
    {
        try {
            // Simple connection test
            self::sendResponse(data: 'Welcome to the Kahuna API!');
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }
}