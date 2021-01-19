<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\Posts\PostsRepository;
use App\Domain\Topics\TopicsRepository;
use App\Domain\User\UserRepository;

abstract class PostsAction extends Action {
    protected $postsRepository;
    protected $topicsRepository;
    protected $userRepository;

    public function __construct(LoggerInterface $logger, PostsRepository $postsRepository,  TopicsRepository $topicsRepository, UserRepository $userRepository) {
        parent::__construct($logger);
        $this->postsRepository = $postsRepository;
        $this->topicsRepository = $topicsRepository;
        $this->userRepository = $userRepository;
    }

    protected function constructPostTopics(array $post, array $postTopics) {
      $topics = array_filter($postTopics, function($postTopic) use ($post) {
        return $post["id"] === $postTopic["postId"];
      });
      $topics = array_map(function ($topic){
        return ["id" => $topic["id"], "name" => $topic["name"]];
      }, $topics);

      return array_values($topics);
    }

    protected function constructPostAuthor(array $post, array $postAuthors) {
      $author = array_filter($postAuthors, function($postAuthor) use ($post) {
        return $post["userId"] === $postAuthor["id"];
      });

      return $author[0];
    }

    protected function constructPostStats(array $post, array $postStats, array $postReplies) {
      $stats = [];
      $stats["upvotes"] = array_reduce($postStats, function($acc, $curr) use ($post) {
        return $post["id"] === $curr["postId"] && $curr["type"] === 1 ? $acc + 1 : $acc;
      }, 0);
      $stats["downvotes"] = array_reduce($postStats, function($acc, $curr) use ($post) {
        return $post["id"] === $curr["postId"] && $curr["type"] === -1 ? $acc + 1 : $acc;
      }, 0);
      $stats["replies"] = array_reduce($postStats, function($acc, $curr) use ($post) {
        return $post["id"] === $curr["postId"] && $curr["type"] === 2 ? $acc + 1 : $acc;
      }, 0);

      return $stats;
    }

    protected function constructPostMedia(array $post, array $postMedia) {
      $media = array_filter($postMedia, function($media) use ($post) {
        return $media["postId"] === $post["id"];
      });

      return $media;
    }

    protected function constructFeedback(array $post, array $postStats, int $userId) {
      $feedback = [];
      $feedback["upvotes"] = array_reduce($postStats, function($acc, $curr) use ($post, $userId) {
        return $post["id"] === $curr["postId"] && intval($curr["userId"]) === $userId && $curr["type"] === 1 ? true : $acc;
      }, false);
      $feedback["downvotes"] = array_reduce($postStats, function($acc, $curr) use ($post, $userId) {
        return $post["id"] === $curr["postId"] && intval($curr["userId"]) === $userId && $curr["type"] === -1 ? true : $acc;
      }, false);
      return $feedback;
    }
}
