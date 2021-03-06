<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \Exception;
use Firebase\JWT\JWT;
use App\Domain\Token\Token;

class ServiceToken implements Token {
  public function verifyToken(string $name, string $token) {
    try {
      $secretKey = $this->getSecretKey($name);
      $decodedToken = JWT::decode($token, $secretKey, ['HS256']);
      return $decodedToken;
    } catch (Exception $error) {
      return false;
    }
  }

  public function createToken(string $name, array $data): array {
    $issueAt = time();
    $expire = $issueAt;

    if ($name === "access token") {
      // 15 mins
      $expire += 60 * 15;
    } elseif ($name === "refresh token") {
      // 30 days
      $expire += 60 * 60 * 24 * 30;
    } else {
      throw new Exception("invalid token name");
    }

    $payload = ["iss" => $issueAt, "exp" => $expire, "data" => $data];
    $secretKey = $this->getSecretKey($name);
    $token = JWT::encode($payload, $secretKey);
    return ["token" => $token, "expire" => $expire];
  }

  public function sendToken(string $name, array $payload) {
    setcookie($name, $payload["token"], $payload["expire"], '/', getenv("COOKIE_DOMAIN"), false, true);
  }

  public function getSecretKey(string $name): string {
    if ($name === "access token") {
      return getenv("ACCESS_TOKEN_SECRET");
    }

    if ($name === "refresh token") {
      return getenv("ACCESS_TOKEN_SECRET");
    }

    throw new Exception("invalid token name");
  }
}
