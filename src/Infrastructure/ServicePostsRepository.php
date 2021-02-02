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
    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $statement = "select posts.* from posts where repliedPostId is null limit ?, ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($limit);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);

    return $data;
  }

  public function findByUserId(int $userId, array $limit): array {
    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
    $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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

  public function findImageByPostId(int $postId): array {
    $statement = "select postimages.* from postimages where postimages.postId = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId]);
    $data = $preparedStatement->fetch(PDO::FETCH_ASSOC);

    return is_array($data) ? $data : [];
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

  public function insertPostReaction(int $postId, int $userId, int $reaction): bool {
    $statement = "insert into poststats (postId, userId, type) values (?, ?, ?)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId, $userId, $reaction]);

    return true;
  }

  public function deletePostReaction(int $postId, int $userId, int $reaction): bool {
    $statement = "delete from poststats where postId = ? and userId = ?and type = ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute([$postId, $userId, $reaction]);

    return true;
  }

  public function deletePost(int $postId) {
    $this->connection->beginTransaction();
    try {
      $statement = "delete from poststats where postId = ?";
      $query = $this->connection->prepare($statement);
      $query->execute([$postId]);
      $statement = "delete from postimages where postId = ?";
      $query = $this->connection->prepare($statement);
      $query->execute([$postId]);
      $statement = "delete from posttopics where postId = ?";
      $query = $this->connection->prepare($statement);
      $query->execute([$postId]);
      $statement = "delete from posts where id = ?";
      $query = $this->connection->prepare($statement);
      $query->execute([$postId]);
      $this->connection->commit();
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }

  public function insertPost(array $payload) {
    $this->connection->beginTransaction();
    try {
      $statement = "insert posts (userId, title, contents) values (?, ?, ?)";
      $query = $this->connection->prepare($statement);
      $query->execute([$payload["user"]["id"], $payload["post"]["title"], isset($payload["post"]["description"]) ? $payload["post"]["description"] : null]);
      $postId = $lastInsertId = $this->connection->lastInsertId();

      if (isset($payload["post"]["image"])) {
        $statement = "insert postimages (postId, publicId, url) values (?, ?, ?)";
        $query = $this->connection->prepare($statement);
        $query->execute([$postId, $payload["post"]["image"]["publicId"], $payload["post"]["image"]["url"]]);
      }

      $topicIdsLength = count($payload["post"]["topics"]);
      $placeholders = array_fill(0, $topicIdsLength, "(?, ?)");
      $placeholders = join(",", $placeholders);
      $statement = "insert posttopics (postId, topicId) values $placeholders";
      $query = $this->connection->prepare($statement);
      $queryPayload = array_map(function ($topicId) use ($postId) {
        return [$postId, $topicId];
      }, $payload["post"]["topics"]);
      $query->execute(array_merge(...$queryPayload));
      $this->connection->commit();
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }

  public function insertPostReply(array $payload) {
    $this->connection->beginTransaction();
    try {
      $statement = "insert posts (userId, contents, repliedPostId) values (?, ?, ?)";
      $query = $this->connection->prepare($statement);
      $query->execute([$payload["user"]["id"], $payload["post"]["description"], $payload["post"]["id"]]);
      $postId = $lastInsertId = $this->connection->lastInsertId();

      if (isset($payload["post"]["image"])) {
        $statement = "insert postimages (postId, publicId, url) values (?, ?, ?)";
        $query = $this->connection->prepare($statement);
        $query->execute([$postId, $payload["post"]["image"]["publicId"], $payload["post"]["image"]["url"]]);
      }

      $this->connection->commit();
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }
}
