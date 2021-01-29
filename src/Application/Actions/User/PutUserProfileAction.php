<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use \Cloudinary\Uploader;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;


class PutUserProfileAction extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    // get form data
    $input = $this->getFormData();

    // get param data
    $usernameParam = $this->resolveArg("username");
    // get user id from token
    $userId = intval($this->request->getAttribute("userId"));
    // if token not available reject request
    if (!$userId) {
      return $this->respondWithData(["message" => "Operasi memerlukan kridenisal"], 401);
    }
    // get user by username param
    $user = $this->userRepository->getUserByUsername($usernameParam);
    // reject if userId from token differrent with user returned by above line
    if (intval($user["id"]) !== $userId) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }
    // get user by username from form data
    $user = $this->userRepository->getUserByUsername($input->username);
    // if username already used reject the request
    if (is_array($user) && count($user) && intval($user["id"]) !== $userId) {
      return $this->respondWithData(["message" => "Username tidak tersedia"], 403);
    }
    // get user by email from form data
    $user = $this->userRepository->getUserByEmail($input->email);
    // if email already used reject the request
    if (is_array($user) && count($user) && intval($user["id"]) !== $userId) {
      return $this->respondWithData(["message" => "Email ini sudah digunakan akun lain"], 403);
    }


    $this->userRepository->updateProfile($userId, $input);
    // get new data from db
    $user = $this->userRepository->getUserById($userId);
    unset($user["createdAt"]);
    return $this->respondWithData($user);
  }
}
