<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;
use App\Domain\TopicsRepositoryInterface;

class TopicsRepository extends Repository implements TopicsRepositoryInterface {
  public function findAll(): array {
    $query = $this->connection->query("select * from topics");
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function findByNames(array $names): array {
    $arrNamesLength = count($names);
    $placeholders = array_fill(0, $arrNamesLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select * from topics where name in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($names);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  // ======== NEW API ========
  public function getAllTopics(): array {
    $query = $this->connection->query("select * from topics");
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function getTopicByName(string $name): array {
    $statement = "select * from topics where name = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$name]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);
    return is_array($data) ? $data : [];
  }
}
