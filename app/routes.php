<?php
declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;

use App\Application\Middleware\JWTMiddleware;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Actions\Auth\SignupAction;
use App\Application\Actions\Auth\LogoutAction;
use App\Application\Actions\Auth\GetUserAction;
use App\Application\Actions\Auth\UpdateUserAction;
use App\Application\Actions\Posts\GetAllPostsAction;
use App\Application\Actions\Posts\GetUserPostsAction;
use App\Application\Actions\Posts\GetUserPostAction;
use App\Application\Actions\Posts\GetUserPostRepliesAction;
use App\Application\Actions\Topics\GetTopicsAction;
use App\Application\Actions\User\GetUserAction as GetUserProfileAction;

return function (App $app) {
  $app->options('/{routes:.*}', function (Request $request, Response $response) {
    // CORS Pre-Flight OPTIONS Request Handler
    return $response;
  });

  $app->group('/auth', function (Group $group) {
    $group->post('/login', LoginAction::class);
    $group->post('/signup', SignupAction::class);
    $group->get('/logout', LogoutAction::class);
    $group->get('/user', GetUserAction::class)->add(JWTMiddleWare::class);
    $group->put('/user', UpdateUserAction::class)->add(JWTMiddleWare::class);
  });

  $app->group('/posts', function (Group $group) {
    $group->get('', GetAllPostsAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}', GetUserPostsAction::class)->add(JWTMiddleWare::class);
    // $group->post('/{username}', PostUserPostAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/{postId}', GetUserPostAction::class)->add(JWTMiddleWare::class);
    // $group->post('/{username}/{postId}', SignupAction::class)->add(JWTMiddleWare::class);
    // $group->delete('/{username}/{postId}', SignupAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/{postId}/replies', GetUserPostRepliesAction::class)->add(JWTMiddleWare::class);
  });

  $app->get('/users/{username}', GetUserProfileAction::class);

  $app->get('/topics', GetTopicsAction::class)->add(JWTMiddleWare::class);

  /**
   * Catch-all route to serve a 404 Not Found page if none of the routes match
   * NOTE: make sure this route is defined last
   */
  $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
  });
};
