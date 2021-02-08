<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Gump;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Token\TokenRepositoryInterface;
use App\Domain\Token\Token;

class Login extends Action {
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
      throw new HttpBadRequestException($this->request, "Tidak bisa login, anda sudah terauthentikasi");
    }

    $this->logger->info("start validating input");
    $gump = new GUMP();

    $gump->validation_rules([
      "username" => ["required"],
      "password" => ["required", "min_len" => 4],
    ]);

    $gump->set_fields_error_messages([
      "username" => ["required" => "Username tidak boleh kosong"],
      "password" => ["required" => "Password tidak boleh kosong", "min_len" => "Password minimal mengandung {param} karakter"],
    ]);

    $gump->filter_rules([
      "username" => ["sanitize_string"],
      "password" => ["sanitize_string"],
    ]);

    $valid_input = $gump->run((array) $this->getFormData());

    $this->logger->info("done validating input");

    if (!$valid_input) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $this->logger->info("Input valid, start getting user data");
    $userData = $this->userRepository->getUserByUsername($valid_input["username"]);
    $this->logger->info("done getting user data");

    if (!$userData) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan pengguna dengan username $valid_input[username]");
    }

    $this->logger->info("user found, start getting password for userId $userData[id]");
    $userPassword = $this->userRepository->getUserPassword($userData["id"]);
    $this->logger->info("done getting password for userId $userData[id]");

    $this->logger->info("start verifying userId $userData[id] password");
    $isPasswordMatch = password_verify($valid_input["password"], $userPassword["hashedPassword"]);

    if (!$isPasswordMatch) {
      throw new HttpForbiddenException($this->request, "Kombinasi username dan password tidak cocok");
    }

    $this->logger->info("done verifying password, password userId $userData[id]  match");

    $this->logger->info("start getting user topics for userId $userData[id]");
    $userTopics = $this->userRepository->getUserTopics($userData["id"]);
    $this->logger->info("done getting user topics for userId $userData[id]");

    $this->logger->info("start getting user avatar for userId $userData[id]");
    $userAvatar = $this->userRepository->getUserAvatar($userData["id"]);
    $this->logger->info("done getting user avatar for userId $userData[id]");

    $responseBody = array_merge($userData, [
      "topics" => $userTopics,
      "avatar" => $userAvatar["url"]
    ]);

    $this->logger->info("start deleting user token in db from previous session (if any) for userId $userData[id]");
    $this->tokenRepository->deleteToken($userData["id"]);
    $this->logger->info("done deleting user token in db from previous session (if any) for userId $userData[id]");

    $this->logger->info("start creating token for userId $userData[id]");
    $accessToken = $this->token->createToken("access token", ["id" => $userData["id"]]);
    $refreshToken = $this->token->createToken("refresh token", ["id" => $userData["id"]]);
    $this->logger->info("done creating token for userId $userData[id]");

    $this->logger->info("start saving token to database for userId $userData[id]");
    $tokenInputPayload = ["userId" => $userData["id"], "token" => $refreshToken["token"]];
    $this->tokenRepository->insertToken($tokenInputPayload);
    $this->logger->info("done saving token to database for userId $userData[id]");

    $this->logger->info("start sending token for userId $userData[id]");
    $this->token->sendToken("accessToken", $accessToken);
    $this->token->sendToken("refreshToken", $refreshToken);
    $this->logger->info("done sending token for userId $userData[id]");
    $this->logger->info("userId $userData[id] has login");
    return $this->respondWithData($responseBody);
  }
}
