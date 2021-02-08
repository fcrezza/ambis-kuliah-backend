<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpBadRequestException;
use Gump;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;

class UpdateUserProfile extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException($this->request, "Operasi memerlukan kridenisal");
    }

    $gump = new GUMP();
    $gump->validation_rules([
      "usernameParam" => ["required"],
      "username" => ["required"],
      "fullname" => ["required"],
      "email" => ["required", "valid_email"],
    ]);
    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "username" => ["required" => "Username tidak boleh kosong"],
      "fullname" => ["required" => "Nama lengkap tidak boleh kosong"],
      "email" => ["required" => "Email tidak boleh kosong", "valid_email" => "Email tidak valid"],
    ]);
    $gump->filter_rules([
      "usernameParam" => ["sanitize_string"],
      "email" => ["sanitize_email", "trim"],
      "username" => ["sanitize_string", "trim"],
      "fullname" => ["sanitize_string", "trim"],
      "bio" => ["sanitize_string", "trim"],
    ]);
    $validInput = $gump->run(array_merge(["usernameParam" => $this->resolveArg("username")
    ], (array) $this->getFormData()));

    if (!$validInput) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($validInput["usernameParam"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "Tidak ada user dengan username $validInput[usernameParam]");
    }

    if ($user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $user = $this->userRepository->getUserByUsername($validInput["username"]);

    if (count($user) && $user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Username ini sudah digunakan");
    }

    $user = $this->userRepository->getUserByEmail($validInput["email"]);

    if (count($user) && $user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Email ini sudah digunakan akun lain");
    }

    unset($validInput["usernameParam"]);
    $this->userRepository->updateProfile(array_merge(["id" => $authenticatedUserId], $validInput));
    $user = $this->userRepository->getUserById($authenticatedUserId);
    unset($user["createdAt"]);
    return $this->respondWithData($user);
  }
}
