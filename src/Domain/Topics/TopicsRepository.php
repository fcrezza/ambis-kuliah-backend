<?php
declare(strict_types=1);

namespace App\Domain\Topics;

interface TopicsRepository {
  public function findAll(): array;
}
