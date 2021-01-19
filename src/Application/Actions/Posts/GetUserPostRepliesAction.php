<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class GetUserPostRepliesAction extends PostsAction {
    protected function action(): ResponseInterface {
      $params = $this->request->getQueryParams();
      $limitParam = [0, 20];

      if (isset($params["limit"])) {
        $limitArr = explode(',', $params["limit"]);
        $limitParam = [(intval($limitArr[0]) - 1), intval($limitArr[1])];
      }

      $username = $this->resolveArg("username");
      $postId = intval($this->resolveArg("postId"));
      $user = $this->userRepository->getUserByUsername($username);

      if (!$user) {
        return $this->respondWithData(["message" => "data tidak dapat ditemukan"], 404);
      }

      $posts = $this->postsRepository->findRepliesByPostIds([$postId], $limitParam);

      if (!$posts) {
        return $this->respondWithData([]);
      }

      $userIds = array_column($posts, "userId");
      $postAuthors = $this->userRepository->findByIds($userIds);
      $postIds = array_column($posts, "id");
      $postStats = $this->postsRepository->findStatsByPostIds($postIds);
      $postReplies = $this->postsRepository->findRepliesByPostIds($postIds);
      $postMedia = $this->postsRepository->findMediaByPostIds($postIds);
      $responseBody = array_map(function($post) use ($postAuthors, $postStats, $postReplies, $postMedia) {
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
