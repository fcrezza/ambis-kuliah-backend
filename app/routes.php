<?php
declare(strict_types=1);

use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Middleware\JWTMiddleware;
use App\Application\Actions\Auth\Login;
use App\Application\Actions\Auth\Logout;
use App\Application\Actions\Auth\Signup;
use App\Application\Actions\Auth\User;
use App\Application\Actions\Topics\GetTopics;
use App\Application\Actions\Posts\GetAllPostsAction;
use App\Application\Actions\Posts\GetTrendingPostsAction;
use App\Application\Actions\Posts\GetUserPostAction;
use App\Application\Actions\Posts\DeleteUserPostAction;
use App\Application\Actions\Posts\GetUserPostsAction;
use App\Application\Actions\Posts\PostUserPostAction;
use App\Application\Actions\Posts\GetUserRepliesAction;
use App\Application\Actions\Posts\InsertPostReplyAction;
use App\Application\Actions\Posts\PostUpvotesPostAction;
use App\Application\Actions\Posts\PostDownvotesPostAction;
use App\Application\Actions\Posts\DeleteUpvotePostAction;
use App\Application\Actions\Posts\DeleteDownvotePostAction;
use App\Application\Actions\Posts\GetUserPostRepliesAction;
use App\Application\Actions\User\GetUser;
use App\Application\Actions\User\InsertUserTopics;
use App\Application\Actions\User\UpdateUserProfile;
use App\Application\Actions\User\UpdateUserAvatar;
use App\Application\Actions\User\UpdateUserTopics;

return function (App $app) {
  $app->options('/{routes:.*}', function (Request $request, Response $response) {
    // CORS Pre-Flight OPTIONS Request Handler
    return $response;
  });

  $app->group('/auth', function (Group $group) {
    $group->post('/login', Login::class);
    $group->post('/signup', Signup::class);
    $group->delete('/logout', Logout::class);
    $group->get('/user', User::class);
  })->add(JWTMiddleWare::class);

  $app->group('/posts', function (Group $group) {
    // this need to change
    $group->get('', GetAllPostsAction::class)->add(JWTMiddleWare::class);
    $group->get('/trending', GetTrendingPostsAction::class);
    // this need to change
    $group->get('/{username}', GetUserPostsAction::class)->add(JWTMiddleWare::class);
    // this need to change
    $group->get('/{username}/replies', GetUserRepliesAction::class)->add(JWTMiddleWare::class);
    $group->post('/{authorUsername}', PostUserPostAction::class)->add(JWTMiddleWare::class);
    $group->get('/{username}/{postId}', GetUserPostAction::class)->add(JWTMiddleWare::class);
    // this need to change
    $group->get('/{username}/{postId}/replies', GetUserPostRepliesAction::class)->add(JWTMiddleWare::class);
    $group->post('/{postId}/upvotes', PostUpvotesPostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{postId}/upvotes/{idUser}', DeleteUpvotePostAction::class)->add(JWTMiddleWare::class);
    $group->post('/{postId}/downvotes', PostDownvotesPostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{postId}/downvotes/{idUser}', DeleteDownvotePostAction::class)->add(JWTMiddleWare::class);
    $group->delete('/{authorUsername}/{postId}', DeleteUserPostAction::class)->add(JWTMiddleWare::class);
    $group->post('/{authorUsername}/{postId}/replies', InsertPostReplyAction::class)->add(JWTMiddleWare::class);
  });

  $app->group("/users", function (Group $group) {
    $group->get('/{username}', GetUser::class);
    $group->post('/{username}/topics', InsertUserTopics::class)->add(JWTMiddleWare::class);
    $group->post('/{username}/avatar', UpdateUserAvatar::class)->add(JWTMiddleWare::class);
    $group->put('/{username}/profile', UpdateUserProfile::class)->add(JWTMiddleWare::class);
    $group->put('/{username}/topics', UpdateUserTopics::class)->add(JWTMiddleWare::class);
  });

  $app->get('/topics', GetTopics::class);

  /**
   * Catch-all route to serve a 404 Not Found page if none of the routes match
   * NOTE: make sure this route is defined last
   */

  $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
  });
};
