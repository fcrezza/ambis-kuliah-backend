<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Gump;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Posts\PostsRepository;
use App\Domain\TopicsRepositoryInterface;

class GetFeeds extends Action {
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

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException($this->request, "Operasi ini memerlukan authentikasi");
    }

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
    ]);

    $validInput = $gump->run($this->request->getQueryParams());

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $limit = isset($validInput["limit"]) ? intval($validInput["limit"]) : 20;
    $after = isset($validInput["after"]) ? intval($validInput["after"]) : null;

    $postsIds = [];
    $userPosts = $this->postsRepository->getPostsByUserId($authenticatedUserId);
    $userTopics = $this->userRepository->getUserTopics($authenticatedUserId);
    $topicIds= array_column($userTopics, "id");
    $topicsPosts = [];

    foreach ($topicIds as $topicId) {
      $topicsPosts[] = $this->postsRepository->getPostsByTopicId($topicId);
    }

    $postsIds = array_unique(array_column(array_merge($userPosts, $topicsPosts), "id"));
    arsort($postsIds, SORT_NUMERIC);
    $postIdsWithLimit = [];

    if ($after) {
      foreach (array_values($postsIds) as $postId) {
        if (count($postIdsWithLimit) === $limit) {
          break;
        }

        if ($postId < $after) {
          $postIdsWithLimit[] = $postId;
        }
      }
    } else {
      $postIdsWithLimit = array_slice($postsIds, 0, $limit);
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

    $nextUrl = count($postIdsWithLimit) >= $limit ? $_SERVER["HTTP_HOST"] . "/posts/feeds?after=" . $postIdsWithLimit[array_key_last($postIdsWithLimit)] . "&limit=$limit" : null;
    return $this->respondWithData(["posts" => $posts, "next" => $nextUrl]);
  }
}
