<?php
declare(strict_types=1);

class Database
{
    private array $config;

    public function __construct()
    {
        $configPath = __DIR__ . '/config.json';
        $config = is_file($configPath)
            ? json_decode((string) file_get_contents($configPath), true)
            : null;

        if (!is_array($config) || !isset($config['database'])) {
            throw new RuntimeException('Configuração do banco de dados inválida.');
        }

        $this->config = $config['database'];
    }

    public function connect(): PDO
    {
        $host = $this->config['host'] ?? 'localhost';
        $dbname = $this->config['dbname'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    }
}
