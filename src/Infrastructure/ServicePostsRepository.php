<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;

use App\Domain\Posts\PostsRepository;

class ServicePostsRepository implements PostsRepository {
  private $connection;

  public function __construct(PDO $conn) {
    $this->connection = $conn;
  }

  public function findOneByPostId(int $postId): array {
    $statement = "select posts.* from posts where id = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);
    return is_array($data) ? $data : [];
  }

  public function findAll(array $limit): array {
    $this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
    $statement = "select posts.* from posts where repliedPostId is null limit ?, ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($limit);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function findByUserId(int $userId, array $limit): array {
    $this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
    $statement = "select posts.* from posts where userId=? and repliedPostId is null limit ?, ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$userId, $limit[0], $limit[1]]);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function findByTopicIds(array $topicIds, array $limit): array {
      $arrIdsLength = count($topicIds);
      $placeholders = array_fill(0, $arrIdsLength, "?");
      $placeholders = join(",", $placeholders);
      $this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
      $statement = "select posts.* from posts left join posttopics on posts.id = posttopics.postId left join topics on posttopics.topicId = topics.id where topics.id in ($placeholders) group by posts.id limit ?, ?";
      $preparedStatement = $this->connection->prepare($statement);
      $preparedStatement->execute(array_merge($topicIds, $limit));
      $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
      return $data;
  }

  public function findTopicsByPostIds(array $postIds): array {
    $arrIdsLength = count($postIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select topics.*, posttopics.postId from posttopics left join topics on topics.id = posttopics.topicId where posttopics.postId in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($postIds);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function findStatsByPostIds(array $postIds): array {
    $arrIdsLength = count($postIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select poststats.* from poststats where poststats.postId in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($postIds);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

    public function findMediaByPostIds(array $postIds): array {
    $arrIdsLength = count($postIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select postmedia.* from postmedia where postmedia.postId in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($postIds);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function findRepliesByPostIds(array $postIds, array $limit = []): array {
    $arrIdsLength = count($postIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "";
    $isLimitExist = count($limit);

    if ($isLimitExist) {
      $statement = "select posts.* from posts where posts.RepliedPostId in ($placeholders) limit ?, ?";
    } else {
      $statement = "select posts.* from posts where posts.RepliedPostId in ($placeholders)";
    }

    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $preparedStatement = $this->connection->prepare($statement);

    if ($isLimitExist) {
      $preparedStatement->execute(array_merge($postIds, $limit));
    } else {
      $preparedStatement->execute($postIds);
    }

    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function findTrendingPosts($limit): array {
    $statement = "select posts.* from posts where posts.timestamp between (now() - interval 7 day) and now() limit ?, ?";
    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($limit);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

    return $data;
  }

  public function findRepliesByUserId(int $userId, array $limit): array {
    $statement = "select posts.* from posts where posts.userId = ? and repliedPostId is not null limit ?, ?";
    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute(array_merge([$userId], $limit));
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function insertPostReaction(int $postId, int $userId, int $reaction): bool  {
    $statement = "insert into poststats (postId, userId, type) values (?, ?, ?)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId, $userId, $reaction]);
    return true;
  }

  public function deletePostReaction(int $postId, int $userId, int $reaction): bool  {
    $statement = "delete from poststats where postId = ? and userId = ?and type = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId, $userId, $reaction]);
    return true;
  }
}
