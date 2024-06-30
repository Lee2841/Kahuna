<?php

namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\{User, AccessToken, Product, Ticket, TicketReply};

class AdminController extends BaseController
{
    public static function login(array $params, array $data): void
    {
        try {
            $email = $data['email'];
            $password = $data['password'];
            $user = User::authenticate($email, $password);
            if ($user) {
                if ($user->isAdmin()) {
                    $token = new AccessToken($user->getId());
                    $token = AccessToken::save($token);
                    self::sendResponse(['user' => $user->getId(), 'isAdmin' => true, 'token' => $token->getToken()]);
                } else {
                    self::sendResponse(null, 401, 'Login failed. User is not an admin.');
                }
            } else {
                self::sendResponse(null, 400, 'Login failed.');
            }
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }
    public static function saveProduct(array $params, array $data): void
    {
        try {
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }

            // Decode the JSON payload
            $input = json_decode(file_get_contents('php://input'), true);

            // Check if the user is logged in and if they are an admin
            $userId = $data['api_user'] ?? null;
            if (!$userId) {
                self::sendResponse(null, 401, 'Unauthorized. User not logged in.');
                return;
            }

            // Retrieve user information
            $user = User::findById($userId);
            if (!$user || !$user->isAdmin()) {
                self::sendResponse(null, 403, 'Forbidden. User is not an admin.');
                return;
            }
            // Proceed with saving the product
            $productName = $data['productName'] ?? null;
            $serialNumber = $data['serialNumber'] ?? null;
            $warrantyPeriod = $data['warrantyPeriod'] ?? null;

            if (!$serialNumber || !$productName || !$warrantyPeriod) {
                self::sendResponse(null, 400, 'Bad Request. Missing required data.');
                return;
            }

            // Create a Product object
            $product = new Product($productName, $serialNumber, $warrantyPeriod);

            // Save the product
            $savedProduct = Product::save($product);

            if ($savedProduct) {
                self::sendResponse(['product' => $savedProduct], 201, 'Product saved successfully.');
            } else {
                self::sendResponse(null, 500, 'Internal Server Error. Failed to save product.');
            }
        } catch (\Exception $e) {
            self::sendResponse(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }


    public static function getAllTickets(array $params, array $data): void
    {
        try {
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }

            // Check if the user is logged in and if they are an admin
            $userId = $data['api_user'] ?? null;
            if (!$userId) {
                self::sendResponse(null, 401, 'Unauthorized. User not logged in.');
                return;
            }

            // Retrieve user information
            $user = User::findById($userId);
            if (!$user || !$user->isAdmin()) {
                self::sendResponse(null, 403, 'Forbidden. User is not an admin.');
                return;
            }

            // Proceed with fetching all tickets
            $tickets = Ticket::getAll();

            self::sendResponse(['tickets' => $tickets ?? []], 200, 'Tickets fetched successfully.');
        } catch (\Exception $e) {
            self::sendResponse(null, 500, 'Internal Server Error');
        }
    }

    public static function getAllUsers(array $params, array $data): void
    {
        try {
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }
            // Check if the user is logged in and if they are an admin
            $userId = $data['api_user'] ?? null;
            if (!$userId) {
                self::sendResponse(null, 401, 'Unauthorized. User not logged in.');
                return;
            }

            // Retrieve user information
            $user = User::findById($userId);
            if (!$user || !$user->isAdmin()) {
                self::sendResponse(null, 403, 'Forbidden. User is not an admin.');
                return;
            }

            // Proceed with fetching all tickets
            $users = User::getAll();

            // Check if any tickets were found
            if ($users) {
                self::sendResponse(['users' => $users], 200, 'Users fetched successfully.');
            } else {
                self::sendResponse(null, 404, 'Not Found. No Users found.');
            }
        } catch (\Exception $e) {
            self::sendResponse(null, 500, 'Internal Server Error');
        }
    }

    public static function saveUser(array $params, array $data): void
    {
        try {
            // Decode the JSON payload
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required data
            $missingFields = [];
            if (!isset($input['name']))
                $missingFields[] = 'name';
            if (!isset($input['surname']))
                $missingFields[] = 'surname';
            if (!isset($input['email']))
                $missingFields[] = 'email';
            if (!isset($input['password']) && empty($params['userId']))
                $missingFields[] = 'password'; // Password is required only for new user
            if (!isset($input['role']))
                $missingFields[] = 'role';

            if (!empty($missingFields)) {
                self::sendResponse(null, 400, 'Bad Request: Missing required fields: ' . implode(', ', $missingFields));
                return;
            }

            // Extract data
            $name = $input['name'];
            $surname = $input['surname'];
            $email = $input['email'];
            $password = $input['password'] ?? null;
            $role = $input['role'];

            // Retrieve user ID from parameters if updating an existing user
            $userIdToUpdate = $params['userId'] ?? null;

            // Determine if this is an update or a create operation
            if ($userIdToUpdate) {
                // Update operation
                $userToUpdate = User::findById($userIdToUpdate);
                if (!$userToUpdate) {
                    self::sendResponse(null, 404, 'Not Found. User not found.');
                    return;
                }

                // Update user details
                $userToUpdate->setName($name);
                $userToUpdate->setSurname($surname);
                $userToUpdate->setEmail($email);
                $userToUpdate->setRole($role);

                if ($password) {
                    $userToUpdate->setPassword($password); // This will hash the password
                }

                // Save the updated user
                $savedUser = User::save($userToUpdate);

                if ($savedUser) {
                    self::sendResponse(['user' => $savedUser], 200, 'User updated successfully.');
                } else {
                    self::sendResponse(null, 500, 'Internal Server Error. Failed to save user.');
                }
            } else {
                // Check if the email already exists
                if (User::findByEmail($email)) {
                    self::sendResponse(null, 409, 'Conflict: Email already exists.');
                    return;
                }

                // Create and save new user
                $user = new User(email: $email, password: $password, name: $name, surname: $surname, role: $role);
                $savedUser = User::save($user);

                if ($savedUser) {
                    self::sendResponse($savedUser, 201, 'User created successfully.');
                } else {
                    self::sendResponse(null, 500, 'Internal Server Error. Failed to save user.');
                }
            }
        } catch (\Exception $e) {
            self::sendResponse(null, 500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public static function getUser(array $params, array $data): void
    {
        try {
            // Ensure the user is authenticated and authorized as admin
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }

            // Check if the user ID is provided in the request parameters
            $userId = $params['userId'] ?? null;
            if (!$userId) {
                self::sendResponse(null, 400, 'Bad Request. User ID not provided.');
                return;
            }

            // Retrieve user information by ID
            $user = User::findById($userId);

            if ($user) {
                // If user found, send success response with user data
                self::sendResponse(['user' => $user], 200, 'User details fetched successfully.');
            } else {
                // If user not found, send not found response
                self::sendResponse(null, 404, 'Not Found. User not found.');
            }
        } catch (\Exception $e) {
            // Handle any internal server errors
            self::sendResponse(null, 500, 'Internal Server Error');
        }
    }

    public static function getAllProducts(array $params, array $data): void
    {
        try {

            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }

            // Check if the user is logged in and if they are an admin
            $userId = $data['api_user'] ?? null;
            if (!$userId) {
                self::sendResponse(null, 401, 'Unauthorized. User not logged in.');
                return;
            }

            // Retrieve user information
            $user = User::findById($userId);
            if (!$user || !$user->isAdmin()) {
                self::sendResponse(null, 403, 'Forbidden. User is not an admin.');
                return;
            }

            // Proceed with fetching all products
            $products = Product::getAllProducts();

            // Check if any products were found
            if ($products) {
                self::sendResponse(['products' => $products], 200, 'Products fetched successfully.');
            } else {
                self::sendResponse(null, 404, 'Not Found. No products found.');
            }
        } catch (\Exception $e) {
            self::sendResponse(null, 500, 'Internal Server Error');
        }
    }

    public static function deleteProduct(array $params, array $data): void
    {
        // Check if the user is logged in and if they are an admin
        $userId = $data['api_user'] ?? null;
        if (!$userId) {
            self::sendResponse(null, 401, 'Unauthorized. User not logged in.');
            return;
        }

        // Retrieve user information
        $user = User::findById($userId);
        if (!$user || !$user->isAdmin()) {
            self::sendResponse(null, 403, 'Forbidden. User is not an admin.');
            return;
        }

        // Check if serial number is provided in the request
        if (!isset($data['serialNumber'])) {
            self::sendResponse(null, 400, 'Bad Request. Serial number not provided.');
            return;
        }

        $serialNumber = $data['serialNumber'];

        // Check if the product is associated with any user before deletion
        if (Product::isProductAssociatedWithUser($serialNumber)) {
            self::sendResponse(null, 403, 'Forbidden. Product is associated with a user.');
            return;
        }

        // Delete the product by serial number
        $result = Product::delete($serialNumber);

        if ($result) {
            self::sendResponse('Product deleted successfully', 200);
        } else {
            self::sendResponse(null, 500, 'Internal Server Error. Failed to delete product.');
        }
    }


    public static function updateTicketStatus(array $params, array $data): void
    {
        try {
            // Ensure the user is authenticated and authorized as admin
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }

            // Check if the ticket ID and new status are provided
            $ticketId = $params['ticketId'] ?? null;
            $newStatus = $data['newStatus'] ?? null;

            if (!$ticketId || !$newStatus) {
                self::sendResponse(null, 400, 'Bad Request. Ticket ID or new status not provided.');
                return;
            }

            // Retrieve the ticket by ID (or assume it's available)
            $ticket = new Ticket('', '', '', 0); // Instantiate an empty ticket object for example

            // Update the ticket status
            $ticket->setId($ticketId);
            $success = $ticket->updateStatus($newStatus);

            if ($success) {
                self::sendResponse(['ticketId' => $ticketId, 'status' => $newStatus], 200, 'Ticket status updated successfully.');
            } else {
                self::sendResponse(null, 500, 'Internal Server Error. Failed to update ticket status.');
            }
        } catch (\Exception $e) {
            // Handle any internal server errors
            self::sendResponse(null, 500, 'Internal Server Error');
        }
    }
}