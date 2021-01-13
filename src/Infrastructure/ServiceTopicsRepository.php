<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;

use App\Domain\Topics\TopicsRepository;

class ServiceTopicsRepository implements TopicsRepository {
  private $connection;

  public function __construct(PDO $conn) {
    $this->connection = $conn;
  }

  public function findAll(): array {
    $query = $this->connection->query("select * from topics");
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }
}