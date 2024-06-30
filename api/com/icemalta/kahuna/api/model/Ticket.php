<?php
namespace com\icemalta\kahuna\api\model;

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\api\model\DBConnect;

class Ticket implements JsonSerializable
{
    private static $db;
    private int $id;
    private string $title;
    private string $productSerialNumber;
    private string $issueDescription;

    private int $userId;
    private string $status;
    private string $createdAt;

    public function __construct(string $title, string $productSerialNumber, string $issueDescription, int $userId, ?string $status = 'Open', ?int $id = 0, ?string $createdAt = null)
    {
        $this->title = $title;
        $this->productSerialNumber = $productSerialNumber;
        $this->issueDescription = $issueDescription;
        $this->userId = $userId;
        $this->status = $status;
        $this->id = $id;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getProductSerialNumber(): string
    {
        return $this->productSerialNumber;
    }

    public function setProductSerialNumber(string $productSerialNumber): self
    {
        $this->productSerialNumber = $productSerialNumber;
        return $this;
    }

    public function getIssueDescription(): string
    {
        return $this->issueDescription;
    }

    public function setIssueDescription(string $issueDescription): self
    {
        $this->issueDescription = $issueDescription;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
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
            'title' => $this->title,
            'productSerialNumber' => $this->productSerialNumber,
            'userId' => $this->userId,
            'status' => $this->status,
            'issueDescription' => $this->issueDescription,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function save(Ticket $ticket): Ticket
    {
        if ($ticket->getId() === 0) {
            // Insert
            $sql = 'INSERT INTO Tickets (title, product_serial_number, status, issue_description, user_id, created_at) VALUES (:title, :productSerialNumber, :status, :issueDescription, :userId, :createdAt)';
            $sth = self::$db->prepare($sql);
        } else {
            // Update
            $sql = 'UPDATE Tickets SET title = :title, product_serial_number = :productSerialNumber, status = :status, issue_description = :issueDescription, user_id = :userId, created_at = :createdAt WHERE id = :id';
            $sth = self::$db->prepare($sql);
            $sth->bindValue('id', $ticket->getId());
        }
        $sth->bindValue('title', $ticket->getTitle());
        $sth->bindValue('productSerialNumber', $ticket->getProductSerialNumber());
        $sth->bindValue('status', $ticket->getStatus());
        $sth->bindValue('issueDescription', $ticket->getIssueDescription());
        $sth->bindValue('userId', $ticket->getUserId());
        $sth->bindValue('createdAt', $ticket->getCreatedAt());
        $sth->execute();
        if ($sth->rowCount() > 0 && $ticket->getId() === 0) {
            $ticket->setId(self::$db->lastInsertId());
        }
        return $ticket;
    }

    public static function findById(int $id): array
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select ticket by ID
        $sql = "SELECT * FROM Tickets WHERE id = :id";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':id', $id, PDO::PARAM_INT);

        $sth->execute();

        $tickets = $sth->fetchAll(PDO::FETCH_ASSOC);

        $ticketObjects = [];
        // Iterate through the results and create Ticket objects
        foreach ($tickets as $ticketData) {
            $ticketObjects[] = new Ticket(
                $ticketData['title'],
                $ticketData['product_serial_number'],
                $ticketData['issue_description'],
                $ticketData['user_id'],
                $ticketData['status'],
            );
        }

        return $ticketObjects;
    }

    public static function findByUserId(int $userId): array
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select tickets by user ID
        $sql = "SELECT * FROM Tickets WHERE user_id = :userId";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':userId', $userId, PDO::PARAM_INT);

        $sth->execute();

        $tickets = $sth->fetchAll(PDO::FETCH_ASSOC);

        $ticketObjects = [];
        // Iterate through the results and create Ticket objects
        foreach ($tickets as $ticketData) {
            $ticketObjects[] = new Ticket(
                $ticketData['title'],
                $ticketData['product_serial_number'],
                $ticketData['issue_description'],
                $ticketData['user_id'],
                $ticketData['status'],
            );
        }

        return $ticketObjects;
    }


    public static function getAll()
    {
        $db = DBConnect::getInstance();
        $connection = $db->getConnection();

        $query = "SELECT * FROM Tickets";

        $statement = $connection->prepare($query);
        $statement->execute();

        $tickets = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $tickets;
    }
    public function updateStatus(string $newStatus): bool
    {
        try {
            $pdo = self::$db;

            // Prepare SQL statement to update ticket status
            $sql = "UPDATE Tickets SET status = :newStatus WHERE id = :id";
            $sth = $pdo->prepare($sql);
            $sth->bindParam(':newStatus', $newStatus, PDO::PARAM_STR);
            $sth->bindParam(':id', $this->id, PDO::PARAM_INT);

            $sth->execute();

            // Check if the update was successful
            return $sth->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}