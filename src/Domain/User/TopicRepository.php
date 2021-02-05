<?php
declare(strict_types=1);

namespace App\Domain\User;

interface TopicRepository {
  // GET OPERATIONS
  public function findByUserId(int $userId): array;
  // INSERT OPERATIONS
  public function insert(int $userId, array $addedTopics);
  // DELETE OPERATIONS
  public function delete(int $userId, array $payload);
}