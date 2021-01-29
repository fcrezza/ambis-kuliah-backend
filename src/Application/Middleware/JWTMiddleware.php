<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use App\Domain\Token\Token;

class JWTMiddleWare {
    private $token;

    public function __construct(Container $container) {
        $this->token = $container->get(Token::class);
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $validAccessToken = '';
        $validRefreshToken = '';

        if (isset($_COOKIE["accessToken"])) {
            $validAccessToken = $this->token->verifyToken("access token", $_COOKIE["accessToken"]);
        } else if (isset($_COOKIE["refreshToken"])) {
            $validRefreshToken = $this->token->verifyToken("refresh token", $_COOKIE["refreshToken"]);
        }

        if ($validAccessToken) {
            $request = $request->withAttribute("userId", intval($validAccessToken->data->id));
            $response = $handler->handle($request);
            return $response;
        }

        if (!$validAccessToken && $validRefreshToken) {
            $accessToken = $this->token->createToken("access token", (array)$validRefreshToken->data);
            $refreshToken = $this->token->createToken("refresh token", (array)$validRefreshToken->data);
            $this->token->sendToken("accessToken", $accessToken);
            $this->token->sendToken("refreshToken", $refreshToken);
            $request = $request->withAttribute("userId", intval($validRefreshToken->data->id));
            $response = $handler->handle($request);
            return $response;
        }

        $response = $handler->handle($request);
        return $response;
    }
}
