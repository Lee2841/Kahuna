<?php
namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\{Ticket, Product};

class TicketController extends BaseController
{
    public static function createTicket(array $params, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['title'], $data['productSerialNumber'], $data['issueDescription'])) {
                self::sendResponse(code: 400, error: 'Bad Request: Missing required fields.');
                return;
            }

            // Extract ticket data
            $title = $data['title'];
            $productSerialNumber = $data['productSerialNumber'];
            $issueDescription = $data['issueDescription'];

            // Check if the product exists
            $product = Product::getProductBySerialNumber($productSerialNumber);
            if (!$product) {
                self::sendResponse(code: 404, error: 'Product not found.');
                return;
            }

            // Check if the product is under warranty
            if (!Product::isProductUnderWarranty($productSerialNumber)) {
                self::sendResponse(code: 422, error: 'Product is not under warranty.');
                return;
            }

            // Extract user ID from token header
            $userId = self::getUserIdFromToken($data);

            // Create and save ticket
            $ticket = new Ticket(
                title: $title,
                productSerialNumber: $productSerialNumber,
                issueDescription: $issueDescription,
                userId: $userId
            );

            $savedTicket = Ticket::save($ticket);

            self::sendResponse(data: $savedTicket, code: 201);
        } catch (\Exception $e) {
            self::sendResponse(code: 500, error: 'Internal Server Error');
        }
    }
}