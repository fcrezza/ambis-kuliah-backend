<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository {
  public function getUserById(int $id);
  public function getUserByUsername(string $username);
  public function getUserByEmail(string $email);
  public function getUserPassword(int $id);
  public function getUserTopics(int $id);
  public function updateUserTopics(int $userId, array $addedTopics, array $deletedTopics);
  public function insertUser(array $data);
  public function findByIds(array $ids): array;
}