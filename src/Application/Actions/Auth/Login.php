<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Token\Token;

class Login extends Action {
   private $token;
   private $userRepository;

    public function __construct(LoggerInterface $logger, Token $token, UserRepository $userRepository) {
        parent::__construct($logger);
        $this->token = $token;
        $this->userRepository = $userRepository;
    }

    protected function action(): ResponseInterface {
      $input = $this->getFormData();
      $userData = $this->userRepository->getUserByUsername($input->username);

      if (!$userData) {
        $errorData = ["message" => "Tidak ditemukan pengguna dengan username " . $input->username];
        return $this->respondWithData($errorData, 404);
      }

      $userPassword = $this->userRepository->getUserPassword((int)$userData["id"]);
      $isPasswordMatch = password_verify($input->password, $userPassword["hashedPassword"]);

      if (!$isPasswordMatch) {
        $errorData = ["message" => "Kombinasi username dan password tidak cocok"];
        return $this->respondWithData($errorData, 403);
      }

      $userTopics = $this->userRepository->getUserTopics((int)$userData["id"]);
      $responseBody = array_merge($userData, ["topics" => $userTopics]);
      $accessToken = $this->token->createToken("access token", ["id" => $userData["id"]]);
      $refreshToken = $this->token->createToken("refresh token", ["id" => $userData["id"]]);
      $this->token->sendToken("accessToken", $accessToken);
      $this->token->sendToken("refreshToken", $refreshToken);
      $this->logger->info($userData["username"] . " has login");
      return $this->respondWithData($responseBody);
    }
}