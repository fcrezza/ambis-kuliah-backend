<?php
declare(strict_types=1);

namespace App\Domain\User;

interface PasswordRepository {
  // GET OPERATIONS
  public function findByUserId(int $userId): array;
  // INSERT OPERATIONS
  public function insert(int $userId, $payload);
  // UPDATE OPERATIONS
  public function update(int $userId, $payload);
}