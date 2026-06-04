<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Database
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function connect(array $config): self
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['name'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return new self($pdo);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }

    public function execute(string $sql, array $params = []): bool
    {
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($params);
    }

    public function call(string $procedure, array $params = []): array
    {
        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        $statement = $this->pdo->prepare(sprintf('CALL %s(%s)', $procedure, $placeholders));
        $statement->execute(array_values($params));
        $rows = $statement->fetchAll();

        while ($statement->nextRowset()) {
        }

        $statement->closeCursor();

        return $rows;
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
