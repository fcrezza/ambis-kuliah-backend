<?php
declare(strict_types=1);

namespace App\Domain\User;

interface AvatarRepository {
  // GET OPERATIONS
  public function findByUserId(int $userId): array;
  // INSERT OPERATIONS
  public function insert(int $userId, array $payload);
  // UPDATE OPERATIONS
  public function update(int $userId, array $payload);
}