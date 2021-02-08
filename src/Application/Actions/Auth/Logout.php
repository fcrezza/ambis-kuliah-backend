<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Domain\Token\TokenRepositoryInterface;
use App\Domain\Token\Token;

class Logout extends Action {
  private $tokenRepository;
  private $token;

  public function __construct(LoggerInterface $logger, TokenRepositoryInterface $tokenRepository, Token $token) {
    parent::__construct($logger);
    $this->tokenRepository = $tokenRepository;
    $this->token = $token;
  }

  protected function action(): ResponseInterface {
    $this->logger->info('hit logout route');
    $userId = $this->request->getAttribute("userId");

    if (!$userId) {
      throw new HttpUnauthorizedException($this->request, "Operasi ini membutuhkan authentikasi");
    }

    $this->logger->info("start deleting user token in db for userId $userId");
    $this->tokenRepository->deleteToken($userId);
    $this->logger->info("done deleting user token in db for userId $userId");

    $this->token->sendToken("accessToken", ["token" => "", "expire" => time() - 3600]);
    $this->token->sendToken("refreshToken", ["token" => "", "expire" => time() - 3600]);
    $this->logger->info("userId $userId has log out");
    return $this->respondWithData(["message" => "berhasil logout"]);
  }
}
