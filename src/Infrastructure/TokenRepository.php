<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;
use App\Domain\Token\TokenRepositoryInterface;

class TokenRepository implements TokenRepositoryInterface {
  private $connection;

  public function __construct(PDO $conn) {
    $this->connection = $conn;
  }

  public function getToken(string $token): array {
    $statement = "select * from tokens where token = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$token]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);
    return is_array($data) ? $data : [];
  }

  public function deleteToken(int $userId) {
    $statement = "delete from tokens where userId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$userId]);
  }

  public function insertToken(array $payload) {
    $statement = "insert into tokens (token, userId) values (?, ?)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$payload["token"], $payload["userId"]]);
  }
}
