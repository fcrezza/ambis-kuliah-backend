<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class GetUserPostAction extends PostsAction {
    protected function action(): ResponseInterface {
      $username = $this->resolveArg("username");
      $postId = intval($this->resolveArg("postId"));
      $user = $this->userRepository->getUserByUsername($username);

      if (!$user) {
        return $this->respondWithData(["message" => "data tidak dapat ditemukan"], 404);
      }

      $post = $this->postsRepository->findOneByPostId($postId);

      if (!$post) {
        return $this->respondWithData(["message" => "data tidak dapat ditemukan"], 404);
      }

      $postId = $post["id"];
      $postTopics = $this->postsRepository->findTopicsByPostIds([$postId]);
      $postStats = $this->postsRepository->findStatsByPostIds([$postId]);
      $postReplies = $this->postsRepository->findRepliesByPostIds([$postId]);
      $postMedia = $this->postsRepository->findMediaByPostIds([$postId]);
      $postDetail = [];
      $postDetail["topics"] = $this->constructPostTopics($post, $postTopics);
      $postDetail["author"] = $user;
      $postDetail["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
      $postDetail["media"] = $this->constructPostMedia($post, $postMedia);
      $userId = intval($this->request->getAttribute("userId"));
      unset($post["userId"]);

      if ($userId) {
        $postDetail["feedback"] = $this->constructFeedback($post, $postStats, $userId);
      }

      $responseBody = array_merge($post, $postDetail);
      return $this->respondWithData($responseBody);
    }
}
