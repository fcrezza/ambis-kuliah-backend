<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Gump;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Posts\PostsRepository;
use Psr\Http\Message\ResponseInterface;
use App\Domain\TopicsRepositoryInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;

class GetPosts extends Action {
  private $postsRepository;
  private $topicsRepository;
  private $userRepository;

  public function __construct(LoggerInterface $logger, PostsRepository $postsRepository, TopicsRepositoryInterface $topicsRepository, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->postsRepository = $postsRepository;
    $this->topicsRepository = $topicsRepository;
    $this->userRepository = $userRepository;
  }

  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");
    $gump = new GUMP();

    $gump->validation_rules([
      "limit" => ["integer"],
      "after" => ["integer"],
    ]);

    $gump->set_fields_error_messages([
      "limit" => ["integer" => "Query limit tidak valid"],
      "after" => ["integer" => "Query after tidak valid"],
    ]);

    $gump->filter_rules([
      "limit" => ["sanitize_numbers"],
      "after" => ["sanitize_numbers"],
      "topic" => ["sanitize_string"],
      "keywords" => ["sanitize_string"],
    ]);

    $validInput = $gump->run($this->request->getQueryParams());

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $limit = isset($validInput["limit"]) ? intval($validInput["limit"]) : 20;
    $after = isset($validInput["after"]) ? intval($validInput["after"]) : null;
    $posts = [];

    if (isset($validInput["topic"])) {
      $topic = $this->topicsRepository->getTopicByName($validInput["topic"]);

      if (!count($topic)) {
        throw new HttpNotFoundException($this->request, "Tidak ada topik dengan nama $validInput[topic]");
      }

      $posts = array_merge($posts, $this->postsRepository->getPostsByTopicId($topic["id"]));
    }

    if (isset($validInput["keywords"])) {
      $posts = array_merge($posts, $this->postsRepository->getPostsByKeywords($validInput["keywords"]));
    }

    if (!isset($validInput["topic"]) && !isset($validInput["keywords"])) {
      $posts = $this->postsRepository->getAllPost();
    }

    $postIds = array_unique(array_column($posts, "id"));
    arsort($postIds, SORT_NUMERIC);
    $postIdsWithLimit = [];

    if ($after) {
      foreach (array_values($postIds) as $postId) {
        if (count($postIdsWithLimit) === $limit) {
          break;
        }

        if ($postId < $after) {
          $postIdsWithLimit[] = $postId;
        }
      }
    } else {
      $postIdsWithLimit = array_slice($postIds, 0, $limit);
    }

    $posts = [];
    foreach ($postIdsWithLimit as $key => $postId) {
      $post = $this->postsRepository->getPostByPostId($postId);
      $postAuthor = $this->userRepository->getUserByid($post["userId"]);
      $authorAvatar = $this->userRepository->getUserAvatar($post["userId"]);
      $postImage = $this->postsRepository->getPostImage($postId);
      $postTopics = $this->postsRepository->getPostTopics($postId);
      $postUpvotes = $this->postsRepository->getPostVotes(["postId" => $postId, "type" => 1]);
      $postDownvotes = $this->postsRepository->getPostVotes(["postId" => $postId, "type" => -1]);
      $postReplies = $this->postsRepository->getPostReplies($postId);

      $post["author"] = array_merge($postAuthor, ["avatar" => $authorAvatar["url"]]);
      $post["image"] = count($postImage) ? $postImage["url"] : null;
      $post["topics"] = $postTopics;
      $post["stats"] = [
        "upvotes" => count($postUpvotes),
        "downvotes" => count($postDownvotes),
        "replies" => count($postReplies)
      ];
      $post["interactions"] = [
        "upvote" => in_array($authenticatedUserId, array_column($postUpvotes, "userId")),
        "downvote" => in_array($authenticatedUserId, array_column($postDownvotes, "userId")),
      ];
      unset($post["author"]["createdAt"], $post["userId"], $post["repliedPostId"]);
      $posts[] = $post;
    }

    $nextUrl = "";

    if (count($postIdsWithLimit) >= $limit) {
      if (isset($validInput["topic"]) && isset($validInput["keywords"])) {
        $nextUrl = $_SERVER["HTTP_HOST"] . "/posts?topic=$validInput[topic]" . "&keywords=$validInput[keywords]" . "&after=" . $postIdsWithLimit[array_key_last($postIdsWithLimit)] . "&limit=$limit";
      } elseif (isset($validInput["topic"])) {
        $nextUrl = $_SERVER["HTTP_HOST"] . "/posts?topic=$validInput[topic]" . "&after=" . $postIdsWithLimit[array_key_last($postIdsWithLimit)] . "&limit=$limit";
      } elseif (isset($validInput["keywords"])) {
        $nextUrl = $_SERVER["HTTP_HOST"] . "/posts?keywords=$validInput[keywords]" . "&after=" . $postIdsWithLimit[array_key_last($postIdsWithLimit)] . "&limit=$limit";
      } else {
        $nextUrl = $_SERVER["HTTP_HOST"] . "/posts?after=" . $postIdsWithLimit[array_key_last($postIdsWithLimit)] . "&limit=$limit";
      }
    }
    return $this->respondWithData(["posts" => $posts, "next" => strlen($nextUrl) ? $nextUrl : null]);
  }
}
