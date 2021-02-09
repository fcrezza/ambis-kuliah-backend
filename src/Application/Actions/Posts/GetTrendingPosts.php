<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Log\LoggerInterface;
use App\Domain\User\UserRepository;
use App\Domain\Posts\PostsRepository;
use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Action;
use App\Domain\TopicsRepositoryInterface;

class GetTrendingPosts extends Action {
  private $postsRepository;
  private $topicsRepository;
  private $userRepository;

  public function __construct(LoggerInterface $logger, PostsRepository $postsRepository, TopicsRepositoryInterface $topicsRepository, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->postsRepository = $postsRepository;
    $this->topicsRepository = $topicsRepository;
    $this->userRepository = $userRepository;
  }

  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");
    $posts = $this->postsRepository->getTrendingPosts();

    if (!$posts) {
      return $this->respondWithData([]);
    }

    $userIds = array_column($posts, "userId");
    $posts = array_map(function ($post) use ($authenticatedUserId) {
      $postAuthor = $this->userRepository->getUserById($post["userId"]);
      $authorAvatar = $this->userRepository->getUserAvatar($postAuthor["id"]);
      $postImage = $this->postsRepository->getPostImage($post["id"]);
      $postTopics = $this->postsRepository->getPostTopics($post["id"]);
      $postUpvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => 1]);
      $postDownvotes = $this->postsRepository->getPostVotes(["postId" => $post["id"], "type" => -1]);
      $postReplies = $this->postsRepository->getPostReplies($post["id"]);

      $post["author"] = array_merge($postAuthor, ["avatar" => $authorAvatar["url"]]);
      $post["image"] = count($postImage) ? $postImage["url"] : null;
      $post["topics"] = $postTopics;
      $post["stats"] = [
        "upvotes" => count($postUpvotes),
        "downvotes" => count($postDownvotes),
        "replies" => count($postReplies)
      ];

      if ($authenticatedUserId) {
        $post["interactions"] = [
          "upvote" => in_array($authenticatedUserId, array_column($postUpvotes, "userId")),
          "downvote" => in_array($authenticatedUserId, array_column($postDownvotes, "userId")),
        ];
      }

      unset($post["author"]["createdAt"], $post["userId"], $post["repliedPostId"]);
      return $post;
    }, $posts);

    return $this->respondWithData($posts);
  }
}
