<?php
/**
 * SFI Queuing System - Database Configuration
 * Centralized PDO connection using singleton pattern.
 */

class Database {
    // Update these values for your environment
    private static $host = 'localhost';
    private static $dbname = 'sfi_queuing_db';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';

    private static $instance = null;

    /**
     * Get a PDO database connection instance.
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection() {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Log the real error but show a generic message to users
                error_log('SFI DB Connection Error: ' . $e->getMessage());
                throw new PDOException('Database connection failed. Please contact the administrator.');
            }
        }

        return self::$instance;
    }

    /**
     * Close the database connection.
     */
    public static function closeConnection() {
        self::$instance = null;
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
