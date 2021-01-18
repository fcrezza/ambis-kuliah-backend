<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Application\Actions\Action;
use App\Domain\Posts\PostsRepository;
use App\Domain\Topics\TopicsRepository;
use App\Domain\User\UserRepository;

class PostsAction extends Action {
    private $postsRepository;
    private $topicsRepository;
    private $userRepository;

    public function __construct(LoggerInterface $logger, PostsRepository $postsRepository,  TopicsRepository $topicsRepository, UserRepository $userRepository) {
        parent::__construct($logger);
        $this->postsRepository = $postsRepository;
        $this->topicsRepository = $topicsRepository;
        $this->userRepository = $userRepository;
    }

    protected function action(): ResponseInterface {
      $params = $this->request->getQueryParams();
      $topicsParam = isset($params["topics"]) ? explode(",", $params["topics"]) : [];
      $limitParam = [0, 20];
      $topicsData = [];
      $posts = [];

      if (isset($params["limit"])) {
        $limitArr = explode(',', $params["limit"]);
        $limitParam = [(intval($limitArr[0]) - 1), intval($limitArr[1])];
      }

      if (count($topicsParam)) {
        $topicsData = $this->topicsRepository->findByNames($topicsParam);
      }

      if (count($topicsData)) {
        $topicIds = array_column($topicsData, "id");
        $posts = $this->postsRepository->findByTopicIds($topicIds, $limitParam);
      } else {
        $posts = $this->postsRepository->findAll($limitParam);
      }

      $userIds = array_column($posts, "userId");
      $postAuthors = $this->userRepository->findByIds($userIds);
      $postIds = array_column($posts, "id");
      $postTopics = $this->postsRepository->findTopicsByPostIds($postIds);
      $postStats = $this->postsRepository->findStatsByPostIds($postIds);
      $postReplies = $this->postsRepository->findRepliesByPostIds($postIds);
      $postMedia = $this->postsRepository->findMediaByPostIds($postIds);
      $responseBody = array_map(function($post) use ($postTopics, $postAuthors, $postStats, $postReplies, $postMedia) {
        $post["topics"] = $this->constructPostTopics($post, $postTopics);
        $post["author"] = $this->constructPostAuthor($post, $postAuthors);
        $post["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
        $post["media"] = $this->constructPostMedia($post, $postMedia);
        $userId = intval($this->request->getAttribute("userId"));
        unset($post["userId"]);

        if ($userId) {
          $post["feedback"] = $this->constructFeedback($post, $postStats, $userId);
        }

        return $post;
      }, $posts);

      return $this->respondWithData($responseBody);
    }

    private function constructPostTopics(array $post, array $postTopics) {
      $topics = array_filter($postTopics, function($postTopic) use ($post) {
        return $post["id"] === $postTopic["postId"];
      });
      $topics = array_map(function ($topic){
        return ["id" => $topic["id"], "name" => $topic["name"]];
      }, $topics);

      return array_values($topics);
    }

    private function constructPostAuthor(array $post, array $postAuthors) {
      $author = array_filter($postAuthors, function($postAuthor) use ($post) {
        return $post["userId"] === $postAuthor["id"];
      });

      return $author[0];
    }

    private function constructPostStats(array $post, array $postStats, array $postReplies) {
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

    private function constructPostMedia(array $post, array $postMedia) {
      $media = array_filter($postMedia, function($media) use ($post) {
        return $media["postId"] === $post["id"];
      });

      return $media;
    }

    private function constructFeedback(array $post, array $postStats, int $userId) {
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
