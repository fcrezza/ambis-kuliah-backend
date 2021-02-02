<?php
declare(strict_types=1);

use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Actions\Posts\GetAllPostsAction;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Middleware\JWTMiddleware;
use App\Application\Actions\Auth\LogoutAction;
use App\Application\Actions\Auth\SignupAction;
use App\Application\Actions\Auth\GetUserAction;
use App\Application\Actions\Auth\UpdateUserAction;
use App\Application\Actions\Topics\GetTopicsAction;
use App\Application\Actions\Posts\GetHotPostsAction;
use App\Application\Actions\Posts\GetUserPostAction;
use App\Application\Actions\Posts\DeleteUserPostAction;
use App\Application\Actions\Posts\GetUserPostsAction;
use App\Application\Actions\Posts\PostUserPostAction;
use App\Application\Actions\Posts\GetUserRepliesAction;
use App\Application\Actions\Posts\PostUpvotesPostAction;
use App\Application\Actions\Posts\PostDownvotesPostAction;
use App\Application\Actions\Posts\DeleteUpvotePostAction;
use App\Application\Actions\Posts\DeleteDownvotePostAction;
use App\Application\Actions\Posts\GetUserPostRepliesAction;
use App\Application\Actions\User\GetUserAction as GetUserProfileAction;
use App\Application\Actions\User\PutUserProfileAction;
use App\Application\Actions\User\PutUserAvatarAction;

return function (App $app) {
  $app->options('/{routes:.*}', function (Request $request, Response $response) {
    // CORS Pre-Flight OPTIONS Request Handler
    return $response;
  });

  $app->group('/auth', function (Group $group) {
    $group->post('/login', LoginAction::class);
    $group->post('/signup', SignupAction::class);
    $group->delete('/logout', LogoutAction::class);
    $group->get('/user', GetUserAction::class)->add(JWTMiddleWare::class);
    $group->put('/user', UpdateUserAction::class)->add(JWTMiddleWare::class);
  });

  $app->group('/posts', function (Group $group) {
    $group->get('', GetAllPostsAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}', GetUserPostsAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/replies', GetUserRepliesAction::class)->add(JWTMiddleWare::class);
    $group->post('/{authorUsername}', PostUserPostAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/{postId}', GetUserPostAction::class)->add(JWTMiddleWare::class);
    $group->post('/{postId}/upvotes', PostUpvotesPostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{postId}/upvotes/{idUser}', DeleteUpvotePostAction::class)->add(JWTMiddleWare::class);
    $group->post('/{postId}/downvotes', PostDownvotesPostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{postId}/downvotes/{idUser}', DeleteDownvotePostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{authorUsername}/{postId}', DeleteUserPostAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/{postId}/replies', GetUserPostRepliesAction::class)->add(JWTMiddleWare::class);
  });

  $app->get('/hotposts', GetHotPostsAction::class);

  $app->get('/users/{username}', GetUserProfileAction::class);
  $app->put('/users/{username}/profile', PutUserProfileAction::class)->add(JWTMiddleWare::class);

  $app->post('/users/{username}/avatar', PutUserAvatarAction::class)->add(JWTMiddleWare::class);

  $app->get('/topics', GetTopicsAction::class);

  /**
   * Catch-all route to serve a 404 Not Found page if none of the routes match
   * NOTE: make sure this route is defined last
   */

  $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
  });
};
