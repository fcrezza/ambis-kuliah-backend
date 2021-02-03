<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Posts\PostsAction;

class GetAllPostsAction extends PostsAction {
  protected function action(): ResponseInterface {
    $params = $this->request->getQueryParams();
    $topicsParam = isset($params["topics"]) ? explode(",", $params["topics"]) : [];
    $keywordsParam = isset($params["keywords"]) ? $params["keywords"] : '';
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
    } elseif ($keywordsParam) {
      $posts = $this->postsRepository->findByKeywords($keywordsParam, $limitParam);
    } else {
      $posts = $this->postsRepository->findAll($limitParam);
    }

    if (!$posts) {
      return $this->respondWithData([]);
    }

    $userIds = array_column($posts, "userId");
    $postAuthors = $this->userRepository->findByIds($userIds);
    $postAuthors = array_map(function ($author) {
      $avatar = $this->userRepository->getAvatarByUserId(intval($author["id"]));
      unset($avatar["publicId"], $avatar["userId"]);

      $author["avatar"] = $avatar;

      return $author;
    }, $postAuthors);
    $postIds = array_column($posts, "id");
    $postTopics = $this->postsRepository->findTopicsByPostIds($postIds);
    $postStats = $this->postsRepository->findStatsByPostIds($postIds);
    $postReplies = $this->postsRepository->findRepliesByPostIds($postIds);
    $postImages = array_map(function ($id) {
      return $this->postsRepository->findImageByPostId($id);
    }, $postIds);
    $responseBody = array_map(function ($post) use ($postTopics, $postAuthors, $postStats, $postReplies, $postImages) {
      $post["topics"] = $this->constructPostTopics($post, $postTopics);
      $post["author"] = $this->constructPostAuthor($post, $postAuthors);
      $post["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
      $post["images"] = $this->constructPostImage($post, $postImages);
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
