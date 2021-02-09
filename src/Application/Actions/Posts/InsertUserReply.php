<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Gump;
use \Cloudinary;
use \Cloudinary\Uploader;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Posts\PostsRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class InsertUserReply extends Action {
  private $postsRepository;
  private $userRepository;

  public function __construct(LoggerInterface $logger, PostsRepository $postsRepository, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->postsRepository = $postsRepository;
    $this->userRepository = $userRepository;
  }

  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException("Operasi memerlukan kredensial");
    }

    $gump = new GUMP();

    $gump->validation_rules([
      "usernameParam" => ["required"],
      "postIdParam" => ["required", "integer"],
      "replyContent" => ["required"],
      "image" => ["extension" => "png;jpg;jpeg;gif"],
    ]);

    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "postIdParam" => ["required" => "Parameter post id tidak boleh kosong", "integer" => "Parameter post id tidak valid"],
      "replyContent" => ["required" => "Isi komentar tidak boleh kosong"],
      "image" => ["extension" => "Ekstensi file tidak didukung"]
    ]);

    $gump->filter_rules([
      "postIdParam" => ["trim", "sanitize_numbers"],
      "usernameParam" => ["trim", "sanitize_string"],
      "replyContent" => ["trim", "sanitize_string"],
    ]);

    $valid_input = $gump->run(array_merge($_POST, $_FILES, ["usernameParam" => $this->resolveArg("username"), "postIdParam" => $this->resolveArg("postId")]));

    if (!is_array($valid_input)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $postAuthor = $this->userRepository->getUserByUsername($valid_input["usernameParam"]);

    if (!count($postAuthor)) {
      throw new HttpNotFoundException($this->request, "Tidak ada user dengan username $valid_input[usernameParam]");
    }

    $post = $this->postsRepository->getPostByPostId(intval($valid_input["postIdParam"]));

    if (!count($post)) {
      throw new HttpNotFoundException($this->request, "Tidak ada post dengan id $valid_input[postIdParam]");
    }

    $user = $this->userRepository->getUserById($authenticatedUserId);

    $image = [];

    if (isset($valid_input["image"])) {
      Cloudinary::config([
        "cloud_name" => $_ENV["CLOUDINARY_CLOUD"],
        "api_key" => $_ENV["CLOUDINARY_KEY"],
        "api_secret" => $_ENV["CLOUDINARY_SECRET"]
      ]);
      $image = Uploader::upload($valid_input["image"]["tmp_name"], ["folder" => "ambiskuliah/posts"]);
    }

    $this->postsRepository->withTransaction(function () use ($user, $image, $valid_input) {
      $this->postsRepository->insertReply(["userId" => $user["id"], "replyContent" => $valid_input["replyContent"], "repliedPostId" => $valid_input["postIdParam"]]);
      $replyId = intval($this->postsRepository->getLastInsertId());

      if (count($image)) {
        $this->postsRepository->insertPostImage(["postId" => $replyId, "publicId" => $image["public_id"], "url" => $image["secure_url"]]);
      }

      $GLOBALS["reply"] = $this->postsRepository->getPostByPostId($replyId);
    });

    $reply = $GLOBALS["reply"];
    $replyAuthorAvatar = $this->userRepository->getUserAvatar($reply["userId"]);
    $replyImage = $this->postsRepository->getPostImage($reply["id"]);
    $reply["topics"] = [];
    $reply["author"] = array_merge($user, ["avatar" => $replyAuthorAvatar["url"]]);
    $reply["image"] = count($replyImage) ? $replyImage["url"] : null;
    $reply["stats"] = ["upvotes" => 0, "downvotes" => 0, "replies" => 0];
    $reply["interactions"] = ["upvote" => false, "downvote" => false];

    $postAuthorAvatar = $this->userRepository->getUserAvatar($postAuthor["id"]);
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
    $post["interactions"] = [
      "upvote" => in_array($authenticatedUserId, array_column($postUpvotes, "userId")),
      "downvote" => in_array($authenticatedUserId, array_column($postDownvotes, "userId")),
    ];
    $repliedPost = array_merge($post, ["author" => $postAuthor]);
    $reply["replyTo"] = $repliedPost;
    unset($reply["author"]["createdAt"], $reply["userId"], $reply["repliedPostId"]);
    return $this->respondWithData($reply);
  }
}
