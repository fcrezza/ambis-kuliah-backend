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

class DeleteUserPost extends Action {
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
      throw new HttpUnauthorizedException($this->request, "Operasi ini memerlukan authentikasi");
    }

    $gump = new GUMP();

    $gump->validation_rules([
      "username" => ["required", ],
      "postId" => ["required", "integer"],
    ]);

    $gump->set_fields_error_messages([
      "username" => ["required" => "Username tidak boleh kosong"],
      "postId" => ["required" => "postId tidak boleh kosong", "integer" => "Parameter postId tidak valid"],
    ]);

    $gump->filter_rules([
      "username" => ["sanitize_string"],
      "postId" => ["sanitize_numbers"],
    ]);

    $validInput = $gump->run(["username" => $this->resolveArg("username"), "postId" => $this->resolveArg("postId")]);

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($validInput["username"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan user dengan username $validInput[username]");
    }

    if ($authenticatedUserId !== $user["id"]) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $post = $this->postsRepository->getPostByPostId(intval($validInput["postId"]));

    if (!count($post)) {
      throw new HttpNotFoundException($this->request, "Tidak ada post dengan id $validInput[postId]");
    }

    if ($post["userId"] !== $user["id"]) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $postImage = $this->postsRepository->getPostImage($post["id"]);

    if (count($postImage)) {
      Cloudinary::config([
        "cloud_name" => $_ENV["CLOUDINARY_CLOUD"],
        "api_key" => $_ENV["CLOUDINARY_KEY"],
        "api_secret" => $_ENV["CLOUDINARY_SECRET"]
      ]);

      Uploader::destroy($postImage["publicId"], ["invalidate" => true]);
    }

    $this->postsRepository->deletePost($post["id"]);

    return $this->respondWithData(["message" => "success"]);
  }
}
