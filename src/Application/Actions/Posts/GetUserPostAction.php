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
      $userAvatar = $this->userRepository->getAvatarByUserId(intval($user["id"]));
      unset($userAvatar["publicId"]);
      unset($userAvatar["userId"]);
      $postTopics = $this->postsRepository->findTopicsByPostIds([$postId]);
      $postStats = $this->postsRepository->findStatsByPostIds([$postId]);
      $postReplies = $this->postsRepository->findRepliesByPostIds([$postId]);
      $postImages = $this->postsRepository->findImageByPostId(intval($postId));
      $postDetail = [];
      $postDetail["topics"] = $this->constructPostTopics($post, $postTopics);
      $postDetail["author"] = array_merge($user, ["avatar" => $userAvatar]);
      $postDetail["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
      $postDetail["images"] = $this->constructPostImage($post, $postImages);
      $postDetail["replyTo"] = null;

      if ($post["repliedPostId"]) {
        $postDetail["replyTo"] = $this->postsRepository->findOneByPostId(intval($post["repliedPostId"]));
        $postDetail["replyTo"]["author"] = $this->userRepository->getUserById(intval($postDetail["replyTo"]["userId"]));
        unset($postDetail["replyTo"]["userId"]);
        unset($postDetail["replyTo"]["repliedPostId"]);
        unset($postDetail["replyTo"]["author"]["createdAt"]);
      }
      unset($post["userId"]);
      unset($post["repliedPostId"]);
      $userId = intval($this->request->getAttribute("userId"));

      if ($userId) {
        $postDetail["feedback"] = $this->constructFeedback($post, $postStats, $userId);
      }

      $responseBody = array_merge($post, $postDetail);
      return $this->respondWithData($responseBody);
    }
}
