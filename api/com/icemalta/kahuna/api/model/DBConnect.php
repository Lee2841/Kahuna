<?php
namespace com\icemalta\kahuna\api\model;

use \PDO;

class DBConnect
{
    private static $singleton = null;
    private $dbh;
    private function __construct()
    {
        // Loads database credentials from a configuration file
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/.env');

        // Connects to the database using the credentials
        $this->dbh = new PDO(
            "mysql:host=mariadb;dbname=kahuna",
            $env['DB_USER'],
            $env['DB_PASS'],
            array(PDO::ATTR_PERSISTENT => true)
        );
    }

    public static function getInstance()
    {
        // Checks if there is an existing database connection. If not, creates a new one.
        self::$singleton = self::$singleton ?? new DBConnect();
        return self::$singleton;
    }

    public function getConnection()
    {
        return $this->dbh;
    }
    public function __serialize(): array
    {
        return [];
    }
    public function __unserialize(array $data): void
    {

    }
}