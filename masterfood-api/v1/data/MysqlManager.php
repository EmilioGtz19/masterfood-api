<?php

require_once 'login_mysql.php';

class MysqlManager
{

    private static $mysqlManager = null;

    private static $pdo;

    final private function __construct()
    {
        try {
            self::getDb();
        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de conexion a base de datos",
                "http://localhost",
                "La conexión al usuario administrador de MySQL se vío afectada. Detalles: " . $e->getMessage());
        }
    }

    public static function get() {
        if (self::$mysqlManager === null) {
            self::$mysqlManager = new self();
        }
        return self::$mysqlManager;
    }

    public function getDb() {
        if (self::$pdo == null) {

            // Parámetros de PDO
            $dsn = sprintf('mysql:dbname=%s; host=%s', MYSQL_DATABASE_NAME, MYSQL_HOST);
            $username = MYSQL_USERNAME;
            $passwd = MYSQL_PASSWORD;
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

            self::$pdo = new PDO($dsn, $username, $passwd, $options);
        }

        return self::$pdo;
    }

    final protected function __clone() {
    }

    function _destructor() {
        self::$pdo = null;
    }
}

?>
