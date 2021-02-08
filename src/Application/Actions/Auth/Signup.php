<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Gump;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Token\Token;
use App\Domain\Token\TokenRepositoryInterface;

class Signup extends Action {
  private $token;
  private $userRepository;
  private $tokenRepository;

  public function __construct(LoggerInterface $logger, Token $token, UserRepository $userRepository, TokenRepositoryInterface $tokenRepository) {
    parent::__construct($logger);
    $this->token = $token;
    $this->userRepository = $userRepository;
    $this->tokenRepository = $tokenRepository;
  }

  protected function action(): ResponseInterface {
    $this->logger->info('hit login route');
    $userId = $this->request->getAttribute("userId");

    if ($userId) {
      throw new HttpBadRequestException($this->request, "Tidak bisa mendaftar, anda sudah terauthentikasi");
    }

    $this->logger->info("start validating input");
    $gump = new GUMP();

    $gump->validation_rules([
      "email" => ["required", "valid_email"],
      "username" => ["required"],
      "fullname" => ["required"],
      "password" => ["required", "min_len" => 4],
      "avatarUrl" => ["required"],
    ]);

    $gump->set_fields_error_messages([
      "email" => ["required" => "Email tidak boleh kosong", "valid_email" => "Alamat email tidak valid"],
      "username" => ["required" => "Username tidak boleh kosong"],
      "fullname" => ["required" => "Nama lengkap tidak boleh kosong"],
      "password" => ["required" => "Password tidak boleh kosong", "min_len" => "Password minimal mengandung {param} karakter"],
      "avatarUrl" => ["required" => "Default avatar tidak boleh kosong"],
    ]);

    $gump->filter_rules([
      "email" => ["sanitize_email"],
      "username" => ["sanitize_string"],
      "fullname" => ["sanitize_string"],
      "password" => ["sanitize_string"],
      "avatarUrl" => ["sanitize_string"],
    ]);

    $valid_input = $gump->run((array) $this->getFormData());
    $this->logger->info("done validating input");

    if (!$valid_input) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $userData = $this->userRepository->getUserByEmail($valid_input["email"]);

    if (count($userData)) {
      throw new HttpForbiddenException($this->request, "Email $valid_input[email] sudah digunakan");
    }

    $userData = $this->userRepository->getUserByUsername($valid_input["username"]);

    if (count($userData)) {
      throw new HttpForbiddenException($this->request, "Username $valid_input[username] sudah digunakan");
    }

    $this->userRepository->withTransaction(function () use ($valid_input) {
      $hashedPassword = password_hash($valid_input["password"], PASSWORD_DEFAULT);
      $this->userRepository->insertUser([
        "username" => $valid_input["username"],
        "fullname" => $valid_input["fullname"],
        "email" => $valid_input["email"],
      ]);
      $userId = $this->userRepository->getLastInsertId();
      $this->userRepository->insertPassword([
        "userId" => $userId,
        "password" => $hashedPassword,
      ]);
      $this->userRepository->insertAvatar(
        [
          "avatar" => [
            "publicId" => null,
            "url" => $valid_input["avatarUrl"],
          ],
          "user" => [
            "id" => $userId
          ]
        ]
      );
    });

    $user = $this->userRepository->getUserByUsername($valid_input["username"]);
    $userAvatar = $this->userRepository->getUserAvatar($user["id"]);
    unset($user["createdAt"]);
    $responseBody = array_merge($user, ["avatar" => $userAvatar["url"], "topics" => []]);

    $accessToken = $this->token->createToken("access token", ["id" => $user["id"]]);
    $refreshToken = $this->token->createToken("refresh token", ["id" => $user["id"]]);
    $this->tokenRepository->insertToken(["token" => $refreshToken["token"], "userId" => $user["id"]]);
    $this->token->sendToken("accessToken", $accessToken);
    $this->token->sendToken("refreshToken", $refreshToken);
    $this->logger->info("userId $user[id] has sign up");
    return $this->respondWithData($responseBody);
  }
}
