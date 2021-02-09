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

class GetUserPost extends Action {
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
      "usernameParam" => ["required"],
      "postIdParam" => ["required", "integer"],
    ]);

    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "postIdParam" => ["required" => "Parameter post id tidak boleh kosong", "integer" => "Parameter post id tidak valid"],
    ]);

    $gump->filter_rules([
      "usernameParam" => ["sanitize_string"],
      "postIdParam" => ["sanitize_numbers"],
    ]);

    $validInput = $gump->run(["usernameParam" => $this->resolveArg("username"), "postIdParam" => $this->resolveArg("postId")]);

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($validInput["usernameParam"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan user dengan username $validInput[usernameParam]");
    }

    $post = $this->postsRepository->getPostByPostId(intval($validInput["postIdParam"]));

    if (!$post) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan post dengan id $validInput[postIdParam] di username $validInput[usernameParam]");
    }

    $authorAvatar = $this->userRepository->getUserAvatar($user["id"]);
    $postImage = $this->postsRepository->getPostImage($post["id"]);
    $postTopics = $this->postsRepository->getPostTopics($post["id"]);
    $postUpvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => 1]);
    $postDownvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => -1]);
    $postReplies = $this->postsRepository->getPostReplies($post["id"]);

    $post["author"] = array_merge($user, ["avatar" => $authorAvatar["url"]]);
    $post["image"] = count($postImage) ? $postImage["url"] : null;
    $post["topics"] = $postTopics;
    $post["stats"] = [
      "upvotes" => count($postUpvotes),
      "downvotes" => count($postDownvotes),
      "replies" => count($postReplies)
    ];

    if ($post["repliedPostId"]) {
      $repliedPost = $this->postsRepository->getPostByPostId($post["repliedPostId"]);
      $repliedPostAuthor = $this->userRepository->getUserByid($repliedPost["userId"]);
      $repliedAuthorAvatar = $this->userRepository->getUserAvatar($repliedPostAuthor["id"]);
      $repliedPostImage = $this->postsRepository->getPostImage($repliedPost["id"]);
      $repliedPostTopics = $this->postsRepository->getPostTopics($repliedPost["id"]);
      $repliedPostUpvotes = $this->postsRepository->getPostVotes(["postId" => $repliedPost["id"], "type" => 1]);
      $repliedPostDownvotes = $this->postsRepository->getPostVotes(["postId" => $repliedPost["id"], "type" => -1]);
      $repliedPostReplies = $this->postsRepository->getPostReplies($repliedPost["id"]);

      $repliedPost["author"] = array_merge($repliedPostAuthor, ["avatar" => $repliedAuthorAvatar["url"]]);
      $repliedPost["image"] = count($repliedPostImage) ? $repliedPostImage["url"] : null;
      $repliedPost["topics"] = $repliedPostTopics;
      $repliedPost["stats"] = [
        "upvotes" => count($repliedPostUpvotes),
        "downvotes" => count($repliedPostDownvotes),
        "replies" => count($repliedPostReplies)
      ];

      if ($authenticatedUserId) {
        $repliedPost["interactions"] = [
          "upvote" => in_array($authenticatedUserId, array_column($repliedPostUpvotes, "userId")),
          "downvote" => in_array($authenticatedUserId, array_column($repliedPostDownvotes, "userId")),
        ];
      }

      unset($repliedPost["author"]["createdAt"], $repliedPost["userId"], $repliedPost["repliedPostId"]);
      $post["replyTo"] = $repliedPost;
    }

    if ($authenticatedUserId) {
      $post["interactions"] = [
        "upvote" => in_array($authenticatedUserId, array_column($postUpvotes, "userId")),
        "downvote" => in_array($authenticatedUserId, array_column($postDownvotes, "userId")),
      ];
    }
    unset($post["author"]["createdAt"], $post["userId"], $post["repliedPostId"]);

    return $this->respondWithData($post);
  }
}
