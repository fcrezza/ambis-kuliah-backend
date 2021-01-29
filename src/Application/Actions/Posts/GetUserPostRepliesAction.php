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
       $postAuthors = array_map(function($author) {
        $avatar = $this->userRepository->getAvatarByUserId(intval($author["id"]));
        unset($avatar["publicId"]);
        unset($avatar["userId"]);
        $author["avatar"] = $avatar;
        return $author;
      }, $postAuthors);
      $postIds = array_column($posts, "id");
      $postStats = $this->postsRepository->findStatsByPostIds($postIds);
      $postReplies = $this->postsRepository->findRepliesByPostIds($postIds);
      $postImages = array_map(function($id) {
        return $this->postsRepository->findImageByPostId($id);
      }, $postIds);
      $responseBody = array_map(function($post) use ($postAuthors, $postStats, $postReplies, $postImages) {
        $post["author"] = $this->constructPostAuthor($post, $postAuthors);
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
