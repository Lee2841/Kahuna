<?php

namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\User;

class UserController extends BaseController
{
    public static function register(array $params, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['name'], $data['surname'], $data['email'], $data['password'])) {
                self::sendResponse(code: 400, error: 'Bad Request: Missing required fields.');
                return;
            }

            // Extract data
            $name = $data['name'];
            $surname = $data['surname'];
            $email = $data['email'];
            $password = $data['password'];

            // Optional role parameter (default to 'customer' if not provided)
            $role = isset($data['role']) ? $data['role'] : 'customer';

            // Check if the email already exists
            if (User::findByEmail($email)) {
                self::sendResponse(code: 409, error: 'Conflict: Email already exists.');
                return;
            }

            // Create and save user
            $user = new User(email: $email, password: $password, name: $name, surname: $surname, role: $role);
            $user = User::save($user);

            self::sendResponse($user, code: 201);
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function getProducts(array $params, array $data): void
    {
        try {
            if (self::checkToken($data)) {
                $userId = $data['api_user'];
                $products = User::getProducts($userId);
                self::sendResponse(data: $products);
            } else {
                self::sendResponse(code: 403, error: 'Missing, invalid or expired token.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function getTickets(array $params, array $data): void
    {
        try {
            // Check token and proceed
            if (self::checkToken($data)) {
                $userId = $data['api_user'];
                $tickets = User::getTickets($userId);
                self::sendResponse(data: $tickets);
            } else {
                self::sendResponse(code: 403, error: 'Missing, invalid or expired token.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function getTicketReplies(array $params, array $data): void
    {
        if (self::checkToken($data)) {
            $userId = $data['api_user'];
            $tickets = User::getTicketReplies($userId);
            self::sendResponse(data: $tickets);
        } else {
            self::sendResponse(code: 403, error: 'Missing, invalid or expired token.');
        }
    }
    public static function getUserInfoById(array $params, array $data): void
    {
        try {
            $userId = $params['userId'];
            $userInfo = User::findById($userId);

            if ($userInfo) {
                $name = $userInfo->getName();
                $surname = $userInfo->getSurname();
                self::sendResponse(['name' => $name, 'surname' => $surname]);
            } else {
                self::sendResponse(code: 404, error: 'User not found.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }
}