<?php
namespace com\icemalta\kahuna\api\model;

use \JsonSerializable;
use \PDO;
use com\icemalta\kahuna\api\model\{DBConnect, TicketReply};

class User implements JsonSerializable
{
    private static $db;
    private int $id;
    private string $name;
    private string $surname;
    private $email;
    private $password;
    private $role;
    public function __construct(?string $email = null, ?string $password = null, ?string $name = null, ?string $surname = null, ?string $role = 'customer', ?int $id = 0)
    {
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->surname = $surname;
        $this->id = $id;
        $this->role = $role;
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === 'agent';
    }


    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'id' => $this->id,
            'role' => $this->role
        ];
    }

    public static function save(User $user): User
    {
        $hashed = password_hash($user->password, PASSWORD_DEFAULT);
        if ($user->getId() === 0) {
            // Insert
            $sql = 'INSERT INTO User(name, surname, email, password, role) VALUES (:name, :surname, :email, :password, :role)';
            $sth = self::$db->prepare($sql);
        } else {
            // Update
            $sql = 'UPDATE User SET name = :name, surname = :surname, email = :email, password = :password, role = :role WHERE id = :id';
            $sth = self::$db->prepare($sql);
            $sth->bindValue('id', $user->getId());
        }
        $sth->bindValue('name', $user->getName());
        $sth->bindValue('surname', $user->getSurname());
        $sth->bindValue('email', $user->getEmail());
        $sth->bindValue('password', $hashed);
        $sth->bindValue('role', $user->getRole());
        $sth->execute();
        if ($sth->rowCount() > 0 && $user->getId() === 0) {
            $user->setId(self::$db->lastInsertId());
        }
        return $user;
    }
    public static function findByEmail(string $email): ?User
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select user by email
        $sql = "SELECT * FROM User WHERE email = :email";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':email', $email, PDO::PARAM_STR);

        $sth->execute();

        $user = $sth->fetch(PDO::FETCH_ASSOC);

        // If user is found, create a User object and return it
        if ($user) {
            return new User($user['name'], $user['surname'], $user['email'], $user['password']);
        } else {
            return null;
        }
    }

    public static function findById(int $id): ?User
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select user by ID
        $sql = "SELECT * FROM User WHERE id = :id";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':id', $id, PDO::PARAM_INT);

        $sth->execute();

        $user = $sth->fetch(PDO::FETCH_ASSOC);

        // If user is found, create a User object and return it
        if ($user) {
            return new User(
                $user['email'],
                $user['password'],
                $user['name'],
                $user['surname'],
                $user['role'],
                $user['id']
            );
        } else {
            return null;
        }
    }

    public static function authenticate(string $email, string $password): ?User
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select user by email
        $sql = "SELECT * FROM User WHERE email = :email";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':email', $email, PDO::PARAM_STR);

        $sth->execute();

        $user = $sth->fetch(PDO::FETCH_ASSOC);

        // If user is found and password is correct, return the User object
        if ($user && password_verify($password, $user['password'])) {
            // Check if the user has agent role
            if ($user['role'] === 'agent') {
                // Return the user with agent role
                return new User(
                    $user['name'],
                    $user['surname'],
                    $user['email'],
                    $user['password'],
                    $user['role'],
                    $user['id']
                );
            } else {
                // Return the user with customer role
                return new User(
                    $user['name'],
                    $user['surname'],
                    $user['email'],
                    $user['password'],
                    'customer', // Default role for non-agent users
                    $user['id']
                );
            }
        }
        return null;
    }

    public static function getProducts(int $userId): array
    {
        self::$db = DBConnect::getInstance()->getConnection();
        // Prepare the SQL query to fetch products by user ID
        $query = "SELECT ProductName, SerialNumber, WarrantyPeriod, WarrantyStartDate, PurchaseDate FROM Products WHERE UserID = :userId";

        $sth = self::$db->prepare($query);
        $sth->bindValue('userId', $userId);
        $sth->execute();

        // Fetch all products associated with the user
        $products = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $products;
    }


    public static function getTickets(int $userId): array
    {
        self::$db = DBConnect::getInstance()->getConnection();
        // Prepare the SQL query to fetch products by user ID
        $query = "SELECT * FROM Tickets WHERE user_id = :userId";

        $sth = self::$db->prepare($query);
        $sth->bindValue('userId', $userId);
        $sth->execute();

        // Fetch all products associated with the user
        $tickets = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $tickets;
    }

    public static function getTicketReplies(int $userId): array
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select ticket replies by ticket ID
        $sql = "SELECT * FROM TicketReplies WHERE user_id = :userId";
        $sth = $pdo->prepare($sql);
        $sth->bindParam(':ticketId', $userId, PDO::PARAM_INT);

        $sth->execute();

        // Fetch all ticket replies for the given ticket ID
        $ticketReplies = [];
        while ($ticketReplyData = $sth->fetch(PDO::FETCH_ASSOC)) {
            $ticketReplies[] = new TicketReply(
                $ticketReplyData['ticket_id'],
                $ticketReplyData['user_id'],
                $ticketReplyData['reply_message'],
                $ticketReplyData['id'],
                $ticketReplyData['created_at']
            );
        }

        return $ticketReplies;
    }

    public static function getAll(): array
    {
        $pdo = DBConnect::getInstance()->getConnection();

        // Prepare SQL statement to select all users
        $sql = "SELECT * FROM User";
        $sth = $pdo->prepare($sql);

        $sth->execute();

        $users = $sth->fetchAll(PDO::FETCH_ASSOC);

        $userObjects = [];
        // Iterate through the results and create User objects
        foreach ($users as $userData) {
            $userObjects[] = new User(
                $userData['email'],
                $userData['password'],
                $userData['name'],
                $userData['surname'],
                $userData['role'],
                $userData['id']
            );
        }

        return $userObjects;
    }
    public static function getInfo(User $user): object
    {
        self::$db = DBConnect::getInstance()->getConnection();
        $sql = 'SELECT  User.email, User.role, 
                COALESCE(COUNT(Product.id), 0) AS products 
                FROM User
                LEFT JOIN Application ON Product.userId = :userId 
                GROUP BY User.email, User.role';
        $sth = self::$db->prepare($sql);
        $sth->bindValue('userId', $user->getId());
        $sth->execute();
        $result = $sth->fetch(PDO::FETCH_OBJ);
        return $result;
    }
}