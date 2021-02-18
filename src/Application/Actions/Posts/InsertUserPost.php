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

class InsertUserPost extends Action {
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

    $decodedPost = array_merge($_POST, ["topics" => json_decode($_POST["topics"])]);

    $gump = new GUMP();

    $gump->validation_rules([
      "usernameParam" => ["required"],
      "title" => ["required"],
      "topics" => ["required", "valid_array_size_greater" => 1, "valid_array_size_lesser" => 3],
      "topics.*" => ["integer"],
      "image" => ["extension" => "png;jpg;jpeg;gif"],
    ]);

    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "title" => ["required" => "Judul post tidak boleh kosong"],
      "topics" => ["required" => "topics tidak boleh kosong", "valid_array_size_greater" => "Post minimal mengandung 1 topik", "valid_array_size_lesser" => "Post maksimal mengandung 3 topik"],
      "topics.*" => ["integer" => "topic id tidak valid"],
      "image" => ["extension" => "Ekstensi file tidak didukung"]
    ]);

    $gump->filter_rules([
      "usernameParam" => ["sanitize_string"],
      "title" => ["trim", "sanitize_string"],
      "description" => ["trim", "sanitize_string"],
      "topics.*" => ["sanitize_numbers"]
    ]);

    $valid_input = $gump->run(array_merge($decodedPost, $_FILES, ["usernameParam" => $this->resolveArg("username")]));

    if (!is_array($valid_input)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($valid_input["usernameParam"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "Tidak ada user dengan username $valid_input[usernameParam]");
    }

    if ($user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $image = [];
    if (isset($valid_input["image"])) {
      Cloudinary::config([
        "cloud_name" => getenv("CLOUDINARY_CLOUD"),
        "api_key" => getenv("CLOUDINARY_KEY"),
        "api_secret" => getenv("CLOUDINARY_SECRET")
      ]);
      $image = Uploader::upload($valid_input["image"]["tmp_name"], ["folder" => "ambiskuliah/posts"]);
    }

    $this->postsRepository->withTransaction(function () use ($user, $image, $valid_input) {
      $this->postsRepository->insertPost(["userId" => $user["id"], "title" => $valid_input["title"], "description" => "$valid_input[description]"]);
      $postId = intval($this->postsRepository->getLastInsertId());

      foreach ($valid_input["topics"] as $topic) {
        $this->postsRepository->insertPostTopic(["postId" => $postId, "topicId" => $topic]);
      }

      if (count($image)) {
        $this->postsRepository->insertPostImage(["postId" => $postId, "publicId" => $image["public_id"], "url" => $image["secure_url"]]);
      }

      $GLOBALS["post"] = $this->postsRepository->getPostByPostId($postId);
    });

    $post = $GLOBALS["post"];
    $userAvatar = $this->userRepository->getUserAvatar($user["id"]);
    $postImage = $this->postsRepository->getPostImage($post["id"]);
    $postTopics = $this->postsRepository->getPostTopics($post["id"]);
    $post["author"] = array_merge($user, ["avatar" => $userAvatar["url"]]);
    $post["image"] = count($postImage) ? $postImage["url"] : null;
    $post["topics"] = $postTopics;
    $post["stats"] = ["upvotes" => 0, "downvotes" => 0, "replies" => 0];
    $post["interactions"] = ["upvote" => false, "downvote" => false];
    unset($post["author"]["createdAt"], $post["userId"], $post["repliedPostId"]);
    return $this->respondWithData($post);
  }
}
