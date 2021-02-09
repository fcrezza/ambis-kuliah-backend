<?php
declare(strict_types=1);

namespace App\Domain\Posts;

interface PostsRepository {
  public function getPostByPostId(int $postId): array;
  public function getPostsByUserId(int $userId): array;
  public function getPostsByTopicId(int $topicId): array;
  public function getPostsByKeywords(string $keywords): array;
  public function getAllPost(): array;
  public function getRepliesByPostId(int $postId): array;
  public function getPostImage(int $postId): array;
  public function getPostTopics(int $postId): array;
  public function getPostReplies(int $postId): array;
  public function getPostVotes(array $payload): array;
  public function insertPost(array $payload);
  public function insertPostTopic(array $payload);
  public function insertPostImage(array $payload);
  public function insertPostVote(array $payload);
  public function insertReply(array $payload);
  public function deletePost(int $postId);
  public function deletePostVote(array $payload);
}
