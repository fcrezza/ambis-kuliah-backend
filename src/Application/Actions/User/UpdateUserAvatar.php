<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Gump;
use \Cloudinary;
use \Cloudinary\Uploader;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;

class UpdateUserAvatar extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException($this->request, "Operasi memerlukan autentikasi");
    }

    $gump = new GUMP();
    $gump->validation_rules([
      "username" => ["required"],
      "avatar" => ["required", "extension" => "png;jpg;jpeg;gif"],
    ]);
    $gump->set_fields_error_messages([
      "username" => ["required" => "Username tidak boleh kosong"],
      "avatar" => ["required" => "file gambar tidak boleh kosong", "extension" => "Ekstensi file tidak didukung"]
    ]);
    $gump->filter_rules(["username" => ["sanitize_string"]]);
    $validInput = $gump->run(array_merge(["username" => $this->resolveArg("username")], $_FILES));

    if (!$validInput) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($validInput["username"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "Tidak ada user dengan username $validInput[username]");
    }

    if ($user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $userAvatar = $this->userRepository->getUserAvatar($user["id"]);
    Cloudinary::config([
      "cloud_name" => getenv("CLOUDINARY_CLOUD"),
      "api_key" => getenv("CLOUDINARY_KEY"),
      "api_secret" => getenv("CLOUDINARY_SECRET")
    ]);

    if ($userAvatar["publicId"]) {
      Uploader::destroy($userAvatar["publicId"], ["invalidate" => true]);
    }

    $res = Uploader::upload($validInput["avatar"]["tmp_name"], ["folder" => "ambiskuliah/users"]);
    $payload = [
      "user" => ["id" => $user["id"]],
      "avatar" => ["publicId" => $res["public_id"], "url" => $res["secure_url"]]
    ];
    $this->userRepository->updateAvatar($payload);
    $newAvatar = $this->userRepository->getAvatarByUserId($user["id"]);
    return $this->respondWithData(["avatar" => $newAvatar["url"]]);
  }
}
