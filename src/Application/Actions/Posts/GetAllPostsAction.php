<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class GetAllPostsAction extends PostsAction {
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
}
