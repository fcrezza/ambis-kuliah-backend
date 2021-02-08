<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository {
  public function getUserById(int $id): array;
  public function getUserByUsername(string $username): array;
  public function getUserByEmail(string $email): array;
  public function getUserTopics(int $id): array;
  public function getUserPassword(int $id): array;
  public function insertUser(array $payload);
  public function insertPassword(array $payload);
  public function insertAvatar(array $payload);
  public function insertUserTopic(array $payload);
  public function updateProfile(array $payload);
  public function updateAvatar(array $payload);
  public function deleteUserTopic(array $payload);
  public function updateUserTopics(int $userId, array $addedTopics, array $deletedTopics);
  public function findByIds(array $ids): array;
  public function getAvatarByUserId(int $userId): array;

  // new API
  public function getUserAvatar(int $userId): array;
}
