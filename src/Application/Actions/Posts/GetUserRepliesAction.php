<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class GetUserRepliesAction extends PostsAction {
    protected function action(): ResponseInterface {
      $params = $this->request->getQueryParams();
      $limitParam = [0, 20];

      if (isset($params["limit"])) {
        $limitArr = explode(',', $params["limit"]);
        $limitParam = [(intval($limitArr[0]) - 1), intval($limitArr[1])];
      }

      $username = $this->resolveArg("username");
      $user = $this->userRepository->getUserByUsername($username);

      if (!$user) {
        return $this->respondWithData(["message" => "data tidak dapat ditemukan"], 404);
      }

      $posts = $this->postsRepository->findRepliesByUserId(intval($user["id"]), $limitParam);

      // return empty array when user dont have posts or when there are no post available to get (ie. usage with limit)
      if (!count($posts)) {
        return $this->respondWithData([]);
      }

      $userAvatar = $this->userRepository->getAvatarByUserId(intval($user["id"]));
      unset($userAvatar["userId"]);
      unset($userAvatar["publicId"]);
      $postIds = array_column($posts, "id");
      $postStats = $this->postsRepository->findStatsByPostIds($postIds);
      $postReplies = $this->postsRepository->findRepliesByPostIds($postIds);
      $postImages = array_map(function($id) {
        return $this->postsRepository->findImageByPostId($id);
      }, $postIds);
      $responseBody = array_map(function($post) use ($user, $userAvatar, $postStats, $postReplies, $postImages) {
        $post["author"] = array_merge($user, ["avatar" => $userAvatar]);
        $post["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
        $post["images"] = $this->constructPostImage($post, $postImages);
        $post["replyTo"] = $this->postsRepository->findOneByPostId(intval($post["repliedPostId"]));
        $post["replyTo"]["author"] = $this->userRepository->getUserById(intval($post["replyTo"]["userId"]));
        unset($post["replyTo"]["userId"]);
        unset($post["replyTo"]["repliedPostId"]);
        unset($post["replyTo"]["author"]["createdAt"]);
        unset($post["userId"]);
        unset($post["repliedPostId"]);

        $userId = intval($this->request->getAttribute("userId"));

        if ($userId) {
          $post["feedback"] = $this->constructFeedback($post, $postStats, $userId);
        }

        return $post;
      }, $posts);
      return $this->respondWithData($responseBody);
    }
}
