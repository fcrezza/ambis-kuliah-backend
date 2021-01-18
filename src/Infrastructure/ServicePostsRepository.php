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

  public function findAll(array $limit): array {
    $this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
    $statement = "select posts.* from posts limit ?, ?";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($limit);
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

  public function findRepliesByPostIds(array $postIds): array {
    $arrIdsLength = count($postIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select posts.* from posts where posts.RepliedPostId in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($postIds);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }
}
