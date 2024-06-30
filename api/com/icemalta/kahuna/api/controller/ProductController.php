<?php

namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\Product;

class ProductController extends BaseController
{
    public static function createProduct(array $params, array $data): void
    {
        try {
            // Validate required data 

            if (!isset($data['productName'], $data['serialNumber'], $data['warrantyPeriod'])) {
                self::sendResponse(code: 400, error: 'Bad Request: Missing required fields.');
                return;
            }

            // Extract data
            $productName = $data['productName'];
            $serialNumber = $data['serialNumber'];
            $warrantyPeriod = $data['warrantyPeriod'];

            // Create and save product
            $product = new Product(productName: $productName, serialNumber: $serialNumber, warrantyPeriod: $warrantyPeriod);
            $product = Product::save($product);

            // Send successful response
            self::sendResponse(data: $product, code: 201);
        } catch (\Exception $e) {
            // Handle any exceptions by sending a 500 error
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }

    public static function associateProduct(array $params, array $data): void
    {
        try {
            if (!self::checkToken($data)) {
                self::sendResponse(null, 401, 'Unauthorized');
                return;
            }
            // Validate required data
            if (!isset($data['serialNumber'])) {
                self::sendResponse(code: 400, error: 'Bad Request: Missing required fields.');
                return;
            }

            $serialNumber = $data['serialNumber'];

            // Check if the product exists
            if (!Product::getProductBySerialNumber($serialNumber)) {
                self::sendResponse(code: 404, error: 'Product not found.');
                return;
            }

            $userId = self::getUserIdFromToken($data);

            $error = Product::associateProductWithUser($serialNumber, $userId);

            // Check if there was an error associating the product with the user
            if ($error !== null) {
                self::sendResponse(code: 400, error: $error);
                return;
            }

            self::sendResponse('Product associated successfully!', code: 201);
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }
}