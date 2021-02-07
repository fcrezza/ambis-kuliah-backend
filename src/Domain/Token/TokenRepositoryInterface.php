<?php
declare(strict_types=1);

namespace App\Domain\Token;

interface TokenRepositoryInterface {
  public function getToken(string $token): array;
  public function insertToken(array $payload);
  public function deleteToken(int $userId);
}
