<?php
declare(strict_types=1);

namespace App\Domain\Token;

interface Token {
  public function createToken(string $name, array $data): array;
  public function verifyToken(string $name, string $token): object;
  public function sendToken(string $name, array $payload);
  public function getSecretKey(string $name): string;
}
