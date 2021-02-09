<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;
use App\Domain\Posts\PostsRepository;

class ServicePostsRepository extends Repository implements PostsRepository {
  public function getPostsByUserId(int $userId): array {
    $statement = "select * from posts where userId = ? and repliedPostId is null";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$userId]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getPostsByTopicId(int $topicId): array {
    $statement = "select posts.* from posts left join posttopics on posts.id = posttopics.postId where posttopics.topicId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$topicId]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getPostsByKeywords(string $keywords): array {
    $statement = "select posts.* from posts where title like ? or contents like ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute(["%$keywords%", "%$keywords%"]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getRepliesByPostId(int $postId):array {
    $statement = "select posts.* from posts where posts.repliedPostId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getPostByPostId(int $postId): array {
    $statement = "select * from posts where id = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);
    return is_array($data) ? $data : [];
  }

  public function getPostImage(int $postId): array {
    $statement = "select * from postimages where postId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);
    return is_array($data) ? $data : [];
  }

  public function getPostVotes(array $payload): array {
    $statement = "select * from postvotes where postId = ? and type = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$payload["postId"], $payload["type"]]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getPostReplies(int $postId): array {
    $statement = "select * from posts where repliedPostId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getPostTopics(int $postId): array {
    $statement = "select topics.* from topics left join posttopics on topics.id = posttopics.topicId where posttopics.postId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

    return $data;
  }

  public function getTrendingPosts(): array {
    $statement = "select p1.* from posts p1 inner join posts p2 on p1.id = p2.repliedPostId left join postvotes v on v.postId = p1.id where p1.repliedPostId is null and p1.timestamp between (now() - interval 7 day) and now() group by p1.id order by count(v.postId) desc, count(p2.id) desc limit 5";
    $preparedStatement = $this->connection->query($statement);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getAllPost(): array {
    $statement = "select posts.* from posts where repliedPostId is null";
    $query = $this->connection->query($statement);
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function insertPost(array $payload) {
    $statement = "insert posts (userId, title, contents) values (?, ?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([$payload["userId"], $payload["title"], $payload["description"]]);
  }

  public function insertPostTopic(array $payload) {
    $statement = "insert posttopics (postId, topicId) values (?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([$payload["postId"], $payload["topicId"]]);
  }

  public function insertPostImage(array $payload) {
    $statement = "insert postimages (postId, publicId, url) values (?, ?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([$payload["postId"], $payload["publicId"], $payload["url"]]);
  }

  public function insertPostVote(array $payload) {
    $statement = "insert postvotes (postId, userId, type) values (?, ?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([$payload["postId"], $payload["userId"], $payload["type"]]);
  }

  public function insertReply(array $payload) {
    $statement = "insert posts (userId, contents, repliedPostId) values (?, ?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([$payload["userId"], $payload["replyContent"], $payload["repliedPostId"]]);
  }

  public function deletePostVote(array $payload) {
    $statement = "delete from postvotes where postId = ? and userId = ? and type = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$payload["postId"], $payload["userId"], $payload["type"]]);
  }

  public function deletePost(int $postId) {
    $statement = "delete from posts where id = ?";
    $query = $this->connection->prepare($statement);
    $query->execute([$postId]);
  }
}
