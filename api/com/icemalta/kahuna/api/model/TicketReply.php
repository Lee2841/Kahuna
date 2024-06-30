<?php

namespace com\icemalta\kahuna\api\model;

use \JsonSerializable;
use PDO;

class TicketReply implements JsonSerializable
{
    private static $db;

    private int $id;
    private int $ticketId;
    private int $userId;
    private string $replyMessage;
    private string $createdAt;

    public function __construct(int $ticketId, int $userId, string $replyMessage, ?int $id = 0, string $createdAt = null)
    {
        $this->ticketId = $ticketId;
        $this->userId = $userId;
        $this->replyMessage = $replyMessage;
        $this->id = $id;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTicketId(): int
    {
        return $this->ticketId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getReplyMessage(): string
    {
        return $this->replyMessage;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTicketId(int $ticketId): self
    {
        $this->ticketId = $ticketId;
        return $this;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setReplyMessage(string $replyMessage): self
    {
        $this->replyMessage = $replyMessage;
        return $this;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'ticketId' => $this->ticketId,
            'userId' => $this->userId,
            'replyMessage' => $this->replyMessage,
            'createdAt' => $this->createdAt,
        ];
    }


    public static function save(TicketReply $reply): TicketReply
    {
        if ($reply->getId() === 0) {
            // Insert
            $sql = 'INSERT INTO TicketReplies (ticket_id, user_id, reply_message, created_at) VALUES (:ticketId, :userId, :replyMessage, :createdAt)';
            $sth = self::$db->prepare($sql);
        } else {
            // Update
            $sql = 'UPDATE TicketReplies SET ticket_id = :ticketId, user_id = :userId, reply_message = :replyMessage, created_at = :createdAt WHERE id = :id';
            $sth = self::$db->prepare($sql);
            $sth->bindValue('id', $reply->getId());
        }
        $sth->bindValue('ticketId', $reply->getTicketId());
        $sth->bindValue('userId', $reply->getUserId());
        $sth->bindValue('replyMessage', $reply->getReplyMessage());
        $sth->bindValue('createdAt', $reply->getCreatedAt());
        $sth->execute();
        if ($sth->rowCount() > 0 && $reply->getId() === 0) {
            $reply->setId(self::$db->lastInsertId());
        }
        return $reply;
    }


    public static function findById(int $ticketId): array
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select ticket replies by ticket ID
        $sql = "SELECT * FROM TicketReplies WHERE ticket_id = :ticketId";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':ticketId', $ticketId, PDO::PARAM_INT);

        $sth->execute();

        // Fetch all ticket replies for the given ticket ID
        $ticketReplies = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $ticketReplies;
    }

}