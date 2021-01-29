<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use \Cloudinary;
use \Cloudinary\Uploader;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;

class PutUserAvatarAction extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    $uploadedFile = $_FILES;
    $usernameParam = $this->resolveArg("username");
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      return $this->respondWithData(["message" => "Operasi memerlukan autentikasi"], 401);
    }

    $user = $this->userRepository->getUserByUsername($usernameParam);

    if (intval($user["id"]) !== $authenticatedUserId) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }

    if (!isset($uploadedFile["avatar"])) {
      return $this->respondWithData(
        ["message" => "Avatar dibutuhkan untuk melakukan operasi ini"],
        403
      );
    }

    $isValidImage = $this->isImage($_FILES["avatar"]["name"]);

    if (!$isValidImage) {
      return $this->respondWithData(["message" => "Ekstensi file tidak didukung"], 403);
    }

    $userAvatar = $this->userRepository->getAvatarByUserId($authenticatedUserId);
    Cloudinary::config([
      "cloud_name" => $_ENV["CLOUDINARY_CLOUD"],
      "api_key" => $_ENV["CLOUDINARY_KEY"],
      "api_secret" => $_ENV["CLOUDINARY_SECRET"]
    ]);

    if ($userAvatar["publicId"]) {
      Uploader::destroy($userAvatar["publicId"], ["invalidate" => true]);
    }

    $res = Uploader::upload($_FILES["avatar"]["tmp_name"], ["folder" => "ambiskuliah/users"]);
    $payload = [
      "user" => ["id" => $authenticatedUserId],
      "avatar" => [
        "publicId" => stripslashes($res["public_id"]),
        "url" => stripslashes($res["secure_url"])
      ]
    ];
    $this->userRepository->updateAvatar($payload);
    $newAvatar = $this->userRepository->getAvatarByUserId($authenticatedUserId);

    return $this->respondWithData($newAvatar);
  }

  private function isImage(string $filename): bool {
    $extensions = ["png", "jpg", "jpeg", "gif"];
    $fileExtension = explode('.', $filename)[1];

    if (in_array($fileExtension, $extensions)) {
      return true;
    }

    return false;
  }
}
