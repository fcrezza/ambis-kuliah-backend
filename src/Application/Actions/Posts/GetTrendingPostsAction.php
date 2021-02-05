<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Posts\PostsAction;

class GetTrendingPostsAction extends PostsAction {
  protected function action(): ResponseInterface {
    $params = $this->request->getQueryParams();
    $limit = [0, 5];

    if (isset($params["limit"])) {
      $limitArr = explode(',', $params["limit"]);
      $limit = [(intval($limitArr[0]) - 1), intval($limitArr[1])];
    }

    $posts = $this->postsRepository->findTrendingPosts($limit);

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
