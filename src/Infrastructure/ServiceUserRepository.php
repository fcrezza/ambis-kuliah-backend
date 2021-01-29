<?php
declare(strict_types=1);

namespace App\Infrastructure;

use \PDO as PDO;
use App\Domain\User\UserRepository;

class ServiceUserRepository implements UserRepository {
  private $connection;

  public function __construct(PDO $conn) {
    $this->connection = $conn;
  }

  public function getUserByUsername(string $username) {
    $query = $this->connection->prepare("select * from users where username = ?");
    $query->execute([$username]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }

  public function findByIds(array $userIds): array {
    $arrIdsLength = count($userIds);
    $placeholders = array_fill(0, $arrIdsLength, "?");
    $placeholders = join(",", $placeholders);
    $statement = "select users.id, users.username, users.fullname, users.email, users.bio from users where users.id in ($placeholders)";
    $preparedStatement = $this->connection->prepare($statement);
    $preparedStatement->execute($userIds);
    $data = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    return $data;
  }

  public function getUserById(int $id) {
    $query = $this->connection->prepare("select * from users where id = ?");
    $query->execute([$id]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }

  public function getUserByEmail(string $email) {
    $query = $this->connection->prepare("select * from users where email = ?");
    $query->execute([$email]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }

  public function getUserPassword(int $id) {
    $query = $this->connection->prepare("select * from userpasswords where userId = ?");
    $query->execute([$id]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }

  public function getUserTopics(int $id) {
    $query = $this->connection->prepare("select t.id, t.name from userTopics u left join topics t on u.topicId = t.id where u.userId = ?");
    $query->execute([$id]);
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function insertUser(array $data) {
    $this->connection->beginTransaction();
    try {
      $userDataQuery = $this->connection->prepare("insert into users (email, username, fullname) values (?, ?, ?)");
      $userDataQuery->execute([$data["email"], $data["username"], $data["fullname"]]);
      $lastInsertId = $this->connection->lastInsertId();
      $passwordQuery = $this->connection->prepare("insert into userpasswords (userId, hashedPassword) values (? ,?)");
      $passwordQuery->execute([$lastInsertId, $data["password"]]);
      $this->connection->commit();
      $userData = $this->getUserById((int)$lastInsertId);
      return array_merge($userData, ["topics" => []]);
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }

  public function updateUserTopics(int $userId,array $addedTopics, array $deletedTopics) {
    $this->connection->beginTransaction();
    try {
      if (count($deletedTopics)) {
        $query = $this->connection->prepare("delete from userTopics where userId = ? and topicId = ?");
        foreach ($deletedTopics as $topic) {
          $query->execute([$userId, $topic]);
        }
      }

      if (count($addedTopics)) {
        $query = $this->connection->prepare("insert userTopics (userId, topicId) values (?, ?)");
        foreach ($addedTopics as $topic) {
          $query->execute([$userId, $topic]);
        }
      }

      $this->connection->commit();
    } catch (PDOException $error) {
      $this->connection->rollback();
      throw $error;
    }
  }

  public function updateProfile(int $id, object $data) {
    $query = $this->connection->prepare("update users set username = ?, fullname = ?, email = ?, bio = ? where users.id = ?");
    $query->execute([$data->username, $data->fullname, $data->email, $data->bio, $id]);
  }

  public function getAvatarByUserId(int $userId): array {
    $statement = "select * from useravatars where userId = ?";
    $query = $this->connection->prepare($statement);
    $query->execute([$userId]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
  }

  public function updateAvatar(array $payload) {
    $statement = "update useravatars set publicId = ?, url = ? where userId = ?";
    $query = $this->connection->prepare($statement);
    $query->execute([
      $payload["avatar"]["publicId"],
      $payload["avatar"]["url"],
      $payload["user"]["id"]
    ]);
  }

  public function insertAvatar(array $payload) {
    $statement = "insert useravatars (publicId, url, userId) values (?, ?, ?)";
    $query = $this->connection->prepare($statement);
    $query->execute([
      $payload["avatar"]["publicId"],
      $payload["avatar"]["url"],
      $payload["user"]["id"]
    ]);
  }
}

