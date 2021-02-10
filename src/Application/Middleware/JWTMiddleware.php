<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Domain\Token\Token;
use App\Domain\Token\TokenRepositoryInterface;
use Psr\Log\LoggerInterface;

class JWTMiddleWare {
  private $token;
  private $logger;
  private $tokenRepository;

  public function __construct(Token $token, LoggerInterface $logger, TokenRepositoryInterface $tokenRepository) {
    $this->token = $token;
    $this->logger = $logger;
    $this->tokenRepository = $tokenRepository;
  }

  public function __invoke(Request $request, RequestHandler $handler): Response {
    $validAccessToken = '';
    $validRefreshToken = '';

    if (isset($_COOKIE["accessToken"])) {
      $this->logger->info("start verifying access token");
      $validAccessToken = $this->token->verifyToken("access token", $_COOKIE["accessToken"]);
      $this->logger->info("done verifying access token");
    } elseif (isset($_COOKIE["refreshToken"])) {
      $this->logger->info("access token not valid, start verifying refresh token");
      $validRefreshToken = $this->token->verifyToken("refresh token", $_COOKIE["refreshToken"]);
      $this->logger->info("done verifying refresh token");
    }

    if ($validAccessToken) {
      $this->logger->info("access token valid go to route");
      $request = $request->withAttribute("userId", intval($validAccessToken->data->id));
      $response = $handler->handle($request);
      return $response;
    }

    if (!$validAccessToken && $validRefreshToken) {
      $this->logger->info("access token not valid, start getting refresh token from db");
      $tokenDB = $this->tokenRepository->getToken($_COOKIE["refreshToken"]);
      $this->logger->info("access token not valid, done getting refresh token from db");

      if (!count($tokenDB)) {
        $this->logger->info("token not found in database,  by pass to the route");
        $response = $handler->handle($request);
        return $response;
      }

      $this->logger->info("refresh token valid, start deleting token in db from previous session if any");
      $this->tokenRepository->deleteToken($validRefreshToken->data->id);
      $this->logger->info("refresh token valid, done deleting token in db from previous session if any");
      $this->logger->info("start creating a new access token and refresh token");
      $accessToken = $this->token->createToken("access token", (array)$validRefreshToken->data);
      $refreshToken = $this->token->createToken("refresh token", (array)$validRefreshToken->data);
      $this->logger->info("done creating a new access token and refresh token");
      $this->logger->info("start saving token to database");
      $this->tokenRepository->insertToken(["token" => $refreshToken["token"], "userId" => $validRefreshToken->data->id]);
      $this->logger->info("done saving token to database");
      $this->logger->info("start sending a new access token and refresh token");
      $this->token->sendToken("accessToken", $accessToken);
      $this->token->sendToken("refreshToken", $refreshToken);
      $this->logger->info("done sending a new access token and refresh token");
      $this->logger->info("go to route");
      $request = $request->withAttribute("userId", intval($validRefreshToken->data->id));
      $response = $handler->handle($request);
      return $response;
    }

    $this->logger->info("access token and refresh token are not valid, by pass to the route");
    $response = $handler->handle($request);
    return $response;
  }
}
