<?php

namespace com\icemalta\kahuna\api\model;

use \JsonSerializable;
use \PDOException;
use \PDO;
use com\icemalta\kahuna\api\model\DBConnect;

class Product implements JsonSerializable
{
    private static $db;
    private int $id;
    private string $serialNumber;
    private $productName;
    private $warrantyPeriod;
    public function __construct(?string $productName = null, ?string $serialNumber = null, ?int $warrantyPeriod = null, int $id = 0)
    {
        $this->serialNumber = $serialNumber;
        $this->productName = $productName;
        $this->warrantyPeriod = $warrantyPeriod;
        $this->id = $id;
        self::$db = DBConnect::getInstance()->getConnection();
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

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): self
    {
        $this->productName = $productName;
        return $this;
    }

    public function getWarrantyPeriod(): int
    {
        return $this->warrantyPeriod;
    }

    public function setWarrantyPeriod(int $warrantyPeriod): self
    {
        $this->warrantyPeriod = $warrantyPeriod;
        return $this;
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'serialNumber' => $this->serialNumber,
            'productName' => $this->productName,
            'warrantyPeriod' => $this->warrantyPeriod,
        ];
    }
    public static function save(Product $product): object
    {
        self::$db = DBConnect::getInstance()->getConnection();

        // Check if the serial number or product name already exists
        $sqlCheck = 'SELECT COUNT(*) FROM Products WHERE serialNumber = :serialNumber OR productName = :productName';
        $sthCheck = self::$db->prepare($sqlCheck);
        $sthCheck->bindValue(':serialNumber', $product->getSerialNumber());
        $sthCheck->bindValue(':productName', $product->getProductName());
        $sthCheck->execute();
        $count = $sthCheck->fetchColumn();

        // Serial number and product name are unique, proceed with inserting the product
        $sql = 'INSERT INTO Products (serialNumber, productName, warrantyPeriod) VALUES (:serialNumber, :productName, :warrantyPeriod)';
        $sth = self::$db->prepare($sql);
        $sth->bindValue(':serialNumber', $product->getSerialNumber());
        $sth->bindValue(':productName', $product->getProductName());
        $sth->bindValue(':warrantyPeriod', $product->getWarrantyPeriod());
        $sth->execute();

        // Check if the insertion was successful
        if ($sth->rowCount() > 0 && $product->getId() === 0) {
            $product->setId(self::$db->lastInsertId());
            return $product;
        } else {
            // Insertion failed, return null to indicate failure
            throw new \RuntimeException('Failed to create product');
        }
    }

    public static function validateSerialNumber(Product $serialNumber): bool
    {

        self::$db = DBConnect::getInstance()->getConnection();

        $query = "SELECT COUNT(*) AS count FROM Products WHERE SerialNumber = :serialNumber";

        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':serialNumber', $serialNumber->getSerialNumber());
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];

        return $count === 1;
    }

    public static function updateWarrantyStartDate(string $serialNumber): int
    {
        self::$db = DBConnect::getInstance()->getConnection();

        // Set the current UTC time as the warranty start date
        $warrantyStartDate = gmdate('Y-m-d H:i:s');

        // Update the warranty start date for the product
        $updateQuery = "UPDATE Products SET WarrantyStartDate = :warrantyStartDate WHERE SerialNumber = :serialNumber";
        $updateStmt = self::$db->prepare($updateQuery);
        $updateStmt->bindParam(':warrantyStartDate', $warrantyStartDate);
        $updateStmt->bindParam(':serialNumber', $serialNumber);
        $updateStmt->execute();

        return $updateStmt->rowCount();
    }


    public static function associateProductWithUser(string $serialNumber, int $userId): ?string
    {
        self::$db = DBConnect::getInstance()->getConnection();

        // Check if the product exists and if it's already associated with another user
        $product = self::getProductBySerialNumber($serialNumber);
        if (!$product) {
            return 'Product not found for the given serial number.';
        }

        // Check if the product is already associated with another user
        if ($product['UserID'] !== null) {
            return 'Product already registered.';
        }

        // Update the warranty start date and associate the product with the user
        $updateQuery = "UPDATE Products SET UserID = :userId, WarrantyStartDate = UTC_TIMESTAMP() WHERE SerialNumber = :serialNumber";
        $updateStmt = self::$db->prepare($updateQuery);
        $updateStmt->bindParam(':userId', $userId);
        $updateStmt->bindParam(':serialNumber', $serialNumber);
        $updateStmt->execute();

        return null;
    }

    public static function getProductBySerialNumber(string $serialNumber)
    {
        self::$db = DBConnect::getInstance()->getConnection();
        if (!self::$db) {
            return null;
        }

        try {
            // Prepare the SQL query to fetch the product by serial number
            $query = "SELECT * FROM Products WHERE SerialNumber = :serialNumber";

            // Prepare and execute the query
            $stmt = self::$db->prepare($query);
            $stmt->bindParam(':serialNumber', $serialNumber);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function isProductAssociatedWithUser(string $serialNumber): bool
    {
        self::$db = DBConnect::getInstance()->getConnection();

        try {
            // Prepare the SQL query to find if UserID is populated for the given SerialNumber
            $query = "SELECT UserID FROM Products WHERE SerialNumber = :serialNumber";

            $stmt = self::$db->prepare($query);
            $stmt->bindParam(':serialNumber', $serialNumber, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $userId = $stmt->fetchColumn();

                return !empty($userId);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }


    public static function getProductsByUserId(int $userId)
    {
        self::$db = DBConnect::getInstance()->getConnection();
        // Prepare the SQL query to fetch products by user ID
        $query = "SELECT ProductName, SerialNumber, WarrantyPeriod, WarrantyStartDate, PurchaseDate FROM Products WHERE UserID = :userId";

        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function isProductUnderWarranty(string $serialNumber): bool
    {
        // Get the product information from the database
        $product = self::getProductBySerialNumber($serialNumber);

        // If product is not found or warranty start date is not set, return false
        if (!$product || !$product['WarrantyStartDate']) {
            return false;
        }

        // Calculate the end date of the warranty period
        $warrantyStartDate = new \DateTime($product['WarrantyStartDate']);
        $warrantyEndDate = $warrantyStartDate->modify('+' . $product['WarrantyPeriod'] . ' years');

        // Get the current date
        $currentDate = new \DateTime();

        // Check if the current date is before the end date of the warranty period
        return $currentDate < $warrantyEndDate;
    }

    public static function getAllProducts(): array
    {
        self::$db = DBConnect::getInstance()->getConnection();

        // Prepare the SQL query to fetch all products
        $query = "SELECT * FROM Products";

        $stmt = self::$db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(string $serialNumber): bool
    {
        self::$db = DBConnect::getInstance()->getConnection();

        try {
            // Prepare SQL statement to delete product by serial number
            $query = "DELETE FROM Products WHERE SerialNumber = :serialNumber";
            $stmt = self::$db->prepare($query);
            $stmt->bindParam(':serialNumber', $serialNumber, PDO::PARAM_STR);

            // Execute the statement
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}