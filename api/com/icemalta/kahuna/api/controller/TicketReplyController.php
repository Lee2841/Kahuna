<?php
namespace com\icemalta\kahuna\api\controller;

use com\icemalta\kahuna\api\model\TicketReply;

class TicketReplyController extends BaseController
{
    public static function reply(array $params, array $data): void
    {

        $userId = self::getUserIdFromToken($data);

        $ticketId = $data['ticketId'];
        $replyMessage = $data['replyMessage'];
        $createdAt = date('Y-m-d H:i:s'); // Current timestamp


        $ticket = new TicketReply(
            ticketId: $ticketId,
            replyMessage: $replyMessage,
            userId: $userId,
            createdAt: $createdAt
        );

        $savedTicket = TicketReply::save($ticket);
        self::sendResponse(data: $savedTicket, code: 201);
    }

    public static function replies(array $params, array $data): void
    {
        if (!self::checkToken($data)) {
            self::sendResponse(null, 401, 'Unauthorized');
            return;
        }

        $ticketId = (int) $data['ticket_id'];

        $ticketReplies = TicketReply::findById($ticketId);

        self::sendResponse($ticketReplies, 200);
    }
}