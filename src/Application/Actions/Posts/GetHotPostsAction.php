<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class GetHotPostsAction extends PostsAction {
    protected function action(): ResponseInterface {
      $params = $this->request->getQueryParams();
      $limit = [1, 5];

      if (isset($params["limit"])) {
        $limitArr = explode(',', $params["limit"]);
        $limitParam = [(intval($limitArr[0]) - 1), intval($limitArr[1])];
      }

      $posts = $this->postsRepository->findTrendingPosts($limit);
      return $this->respondWithData(["message" => $posts], 404);

      // if (!$posts) {
      //   return $this->respondWithData(["message" => "data tidak dapat ditemukan"], 404);
      // }

      // $postId = $post["id"];
      // $postTopics = $this->postsRepository->findTopicsByPostIds([$postId]);
      // $postStats = $this->postsRepository->findStatsByPostIds([$postId]);
      // $postReplies = $this->postsRepository->findRepliesByPostIds([$postId]);
      // $postMedia = $this->postsRepository->findMediaByPostIds([$postId]);
      // $postDetail = [];
      // $postDetail["topics"] = $this->constructPostTopics($post, $postTopics);
      // $postDetail["author"] = $user;
      // $postDetail["stats"] = $this->constructPostStats($post, $postStats, $postReplies);
      // $postDetail["media"] = $this->constructPostMedia($post, $postMedia);
      // $userId = intval($this->request->getAttribute("userId"));
      // unset($post["userId"]);

      // if ($userId) {
      //   $postDetail["feedback"] = $this->constructFeedback($post, $postStats, $userId);
      // }

      // $responseBody = array_merge($post, $postDetail);
      // return $this->respondWithData($responseBody);
    }
}
