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
use App\Application\Actions\Posts\GetPosts;
use App\Application\Actions\Posts\GetFeeds;
use App\Application\Actions\Posts\GetTrendingPosts;
use App\Application\Actions\Posts\GetUserPost;
use App\Application\Actions\Posts\DeleteUserPost;
use App\Application\Actions\Posts\GetUserPosts;
use App\Application\Actions\Posts\InsertUserPost;
use App\Application\Actions\Posts\GetUserRepliesAction;
use App\Application\Actions\Posts\InsertUserReply;
use App\Application\Actions\Posts\InsertPostUpvote;
use App\Application\Actions\Posts\InsertPostDownvote;
use App\Application\Actions\Posts\DeletePostUpvote;
use App\Application\Actions\Posts\DeletePostDownvote;
use App\Application\Actions\Posts\GetPostReplies;
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
    $group->get('', GetPosts::class);
    $group->get('/feeds', GetFeeds::class);
    $group->get('/trending', GetTrendingPosts::class);
    $group->get('/{username}', GetUserPosts::class);
    $group->get('/{username}/{postId}', GetUserPost::class);
    $group->get('/{username}/{postId}/replies', GetPostReplies::class);
    $group->post('/{username}', InsertUserPost::class);
    $group->post('/{username}/{postId}/replies', InsertUserReply::class);
    $group->post('/{postId}/upvotes', InsertPostUpvote::class);
    $group->post('/{postId}/downvotes', InsertPostDownvote::class);
    $group->delete('/{username}/{postId}', DeleteUserPost::class);
    $group->delete('/{postId}/upvotes/{idUser}', DeletePostUpvote::class);
    $group->delete('/{postId}/downvotes/{idUser}', DeletePostDownvote::class);
    // this need to change
    $group->get('/{username}/replies', GetUserRepliesAction::class);
  })->add(JWTMiddleWare::class);

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
