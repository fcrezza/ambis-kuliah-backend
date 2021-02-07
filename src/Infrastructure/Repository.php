<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;

abstract class Repository {
  protected $connection;

  public function __construct(PDO $connection) {
    $this->connection = $connection;
  }

  public function withTransaction(callable $callback) {
    $this->connection->beginTransaction();
    try {
      $callback();
      $this->connection->commit();
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }

  public function getLastInsertId() {
    return $this->connection->lastInsertId();
  }
}
