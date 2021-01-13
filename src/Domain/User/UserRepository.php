<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository {
  public function getUserById(int $id);
  public function getUserByUsername(string $username);
  public function getUserByEmail(string $email);
  public function getUserPassword(int $id);
  public function getUserTopics(int $id);
  public function insertUser(array $data);
}