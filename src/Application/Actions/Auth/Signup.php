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

class Signup extends Action {
   private $token;
   private $userRepository;

    public function __construct(LoggerInterface $logger, Token $token, UserRepository $userRepository) {
        parent::__construct($logger);
        $this->token = $token;
        $this->userRepository = $userRepository;
    }

    protected function action(): ResponseInterface {
      $input = $this->getFormData();
      $userData = $this->userRepository->getUserByEmail($input->email);

      if ($userData) {
        $errorData = ["message" => "Email " . $input->email . " sudah digunakan"];
        return $this->respondWithData($errorData, 404);
      }

      $userData = $this->userRepository->getUserByUsername($input->username);

      if ($userData) {
        $errorData = ["message" => "Username " . $input->username . " sudah digunakan"];
        return $this->respondWithData($errorData, 404);
      }

      $hashedPassword = password_hash($input->password, PASSWORD_DEFAULT);
      $responseBody = $this->userRepository->insertUser([
        "username" => $input->username,
        "fullname" => $input->fullname,
        "email" => $input->email,
        "password" => $hashedPassword
      ]);

      $accessToken = $this->token->createToken("access token", ["id" => $responseBody["id"]]);
      $refreshToken = $this->token->createToken("refresh token", ["id" => $responseBody["id"]]);
      $this->token->sendToken("accessToken", $accessToken);
      $this->token->sendToken("refreshToken", $refreshToken);
      $this->logger->info($responseBody["username"] . "has sign up");
      return $this->respondWithData($responseBody);
    }
}