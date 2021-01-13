<?php
declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;

use App\Application\Middleware\JWTMiddleware;
use App\Application\Actions\Auth\Login;
use App\Application\Actions\Auth\Signup;
use App\Application\Actions\Auth\Logout;
use App\Application\Actions\Auth\User;
use App\Application\Actions\Topics\TopicsAction;

return function (App $app) {
  $app->options('/{routes:.*}', function (Request $request, Response $response) {
    // CORS Pre-Flight OPTIONS Request Handler
    return $response;
  });

  $app->get('/topics', TopicsAction::class)->add(JWTMiddleWare::class);

  $app->group('/auth', function (Group $group) {
    $group->post('/login', Login::class);
    $group->post('/signup', Signup::class);
    $group->get('/logout', Logout::class);
    $group->get('/user', User::class)->add(JWTMiddleWare::class);
  });

  /**
   * Catch-all route to serve a 404 Not Found page if none of the routes match
   * NOTE: make sure this route is defined last
   */
  $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
      throw new HttpNotFoundException($request);
  });
};
