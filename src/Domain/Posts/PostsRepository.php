<?php
declare(strict_types=1);

namespace App\Domain\Posts;

interface PostsRepository {
  public function findAll(array $limit): array;
  public function findByTopicIds(array $topicIds, array $limit): array;
  public function findTopicsByPostIds(array $postIds): array;
  public function findStatsByPostIds(array $postIds): array;
  public function findMediaByPostIds(array $postIds): array;
  public function findRepliesByPostIds(array $postIds): array;
}