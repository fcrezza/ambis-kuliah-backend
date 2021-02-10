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

class GetPostReplies extends Action {
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
      "postIdParam" => ["integer"],
    ]);

    $gump->set_fields_error_messages([
      "limit" => ["integer" => "Query limit tidak valid"],
      "after" => ["integer" => "Query after tidak valid"],
      "postIdParam" => ["integer" => "Parameter postId tidak valid"],
    ]);

    $gump->filter_rules([
      "limit" => ["sanitize_numbers"],
      "after" => ["sanitize_numbers"],
      "usernameParam" => ["sanitize_string"],
      "postIdParam" => ["sanitize_numbers"],
    ]);

    $validInput = $gump->run(array_merge($this->request->getQueryParams(), ["usernameParam" => $this->resolveArg("username"), "postIdParam" => $this->resolveArg("postId")]));

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $postAuthor = $this->userRepository->getUserByUsername($validInput["usernameParam"]);

    if (!$postAuthor) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan user dengan username $validInput[usernameParam]");
    }
    $postAuthorAvatar = $this->userRepository->getUserAvatar($postAuthor["id"]);

    $post = $this->postsRepository->getPostByPostId(intval($validInput["postIdParam"]));

    if (!count($post)) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan post dengan id $validInput[postIdParam] pada username $validInput[usernameParam]");
    }

    $postImage = $this->postsRepository->getPostImage($post["id"]);
    $postTopics = $this->postsRepository->getPostTopics($post["id"]);
    $postUpvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => 1]);
    $postDownvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => -1]);
    $postReplies = $this->postsRepository->getPostReplies($post["id"]);
    unset($postAuthor["createdAt"], $post["userId"], $post["repliedPostId"]);
    $post["author"] = array_merge($postAuthor, ["avatar" => $postAuthorAvatar["url"]]);
    $post["image"] = count($postImage) ? $postImage["url"] : null;
    $post["topics"] = $postTopics;
    $post["stats"] = [
      "upvotes" => count($postUpvotes),
      "downvotes" => count($postDownvotes),
      "replies" => count($postReplies)
    ];

    if ($authenticatedUserId) {
      $post["interactions"] = [
        "upvote" => in_array($authenticatedUserId, array_column($postUpvotes, "userId")),
        "downvote" => in_array($authenticatedUserId, array_column($postDownvotes, "userId")),
      ];
    }

    $limit = isset($validInput["limit"]) ? intval($validInput["limit"]) : 20;
    $after = isset($validInput["after"]) ? intval($validInput["after"]) : null;
    $replyIds = [];
    $postReplies = $this->postsRepository->getRepliesByPostId($post["id"]);

    $replyIds = array_column($postReplies, "id");
    arsort($replyIds, SORT_NUMERIC);
    $replyIdsWithLimit = [];

    if ($after) {
      foreach (array_values($replyIds) as $replyId) {
        if (count($replyIdsWithLimit) === $limit) {
          break;
        }

        if ($replyId < $after) {
          $replyIdsWithLimit[] = $replyId;
        }
      }
    } else {
      $replyIdsWithLimit = array_slice($replyIds, 0, $limit);
    }

    $replies = [];
    foreach ($replyIdsWithLimit as $key => $replyId) {
      $reply = $this->postsRepository->getPostByPostId($replyId);
      $replyAuthor = $this->userRepository->getUserByid($reply["userId"]);
      $authorAvatar = $this->userRepository->getUserAvatar($reply["userId"]);
      $replyImage = $this->postsRepository->getPostImage($replyId);
      $replyUpvotes = $this->postsRepository->getPostVotes(["postId" => $replyId, "type" => 1]);
      $replyDownvotes = $this->postsRepository->getPostVotes(["postId" => $replyId, "type" => -1]);
      $replyReplies = $this->postsRepository->getPostReplies($replyId);
      $repliedPost = array_merge($post, ["author" => $postAuthor]);

      $reply["author"] = array_merge($replyAuthor, ["avatar" => $authorAvatar["url"]]);
      $reply["image"] = count($replyImage) ? $replyImage["url"] : null;
      $reply["stats"] = [
        "upvotes" => count($replyUpvotes),
        "downvotes" => count($replyDownvotes),
        "replies" => count($replyReplies)
      ];
      $reply["replyTo"] = $repliedPost;

      if ($authenticatedUserId) {
        $reply["interactions"] = [
          "upvote" => in_array($authenticatedUserId, array_column($replyUpvotes, "userId")),
          "downvote" => in_array($authenticatedUserId, array_column($replyDownvotes, "userId")),
        ];
      }

      unset($reply["author"]["createdAt"], $reply["userId"], $reply["repliedPostId"]);
      $replies[] = $reply;
    }

    $nextUrl = count($replyIdsWithLimit) >= $limit ? $_SERVER["HTTP_HOST"] . "/posts/$postAuthor[username]/$post[id]/replies?after=" . $replyIdsWithLimit[array_key_last($replyIdsWithLimit)] . "&limit=$limit" : null;
    return $this->respondWithData(["posts" => $replies, "next" => $nextUrl]);
  }
}
