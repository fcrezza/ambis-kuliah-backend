<?php
declare(strict_types=1);

namespace App\Domain;

interface TopicsRepositoryInterface {
  public function findAll(): array;
  public function findByNames(array $names): array;

  // ======== NEW API ========

  public function getAllTopics(): array;
  public function getTopicByName(string $name): array;
}
