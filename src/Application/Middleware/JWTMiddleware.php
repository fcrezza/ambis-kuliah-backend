<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use App\Domain\Token\Token;

class JWTMiddleWare {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $response = $handler->handle($request);
        $token = $this->container->get(Token::class);
        if (!isset($_COOKIE["accessToken"])) {
          $response->getBody()->write("waala access");
          return $response->withStatus(403);
        } else if (!isset($_COOKIE["refreshToken"])) {
            $response->getBody()->write("waala refresh");
            return $response->withStatus(403);
        }

        return $response;
    }
}
