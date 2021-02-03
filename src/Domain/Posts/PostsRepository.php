<?php
declare(strict_types=1);

namespace App\Domain\Posts;

interface PostsRepository {
  public function findAll(array $limit): array;

  public function findOneByPostId(int $postId): array;

  public function findByTopicIds(array $topicIds, array $limit): array;

  public function findByKeywords(string $keywords, array $limit): array;

  public function findByUserId(int $userId, array $limit): array;

  public function findTopicsByPostIds(array $postIds): array;

  public function findStatsByPostIds(array $postIds): array;

  public function findRepliesByPostIds(array $postIds, array $limit): array;

  public function findTrendingPosts(array $limit): array;

  public function findRepliesByUserId(int $userId, array $limit): array;

  public function findImageByPostId(int $postId): array;

  public function insertPostReaction(int $postId, int $userId, int $reaction): bool;

  public function deletePostReaction(int $postId, int $userId, int $reaction): bool;

  public function deletePost(int $postId);

  public function insertPost(array $payload);

  public function insertPostReply(array $payload);
}
