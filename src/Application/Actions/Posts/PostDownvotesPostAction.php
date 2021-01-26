<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;

use App\Application\Actions\Posts\PostsAction;

class PostDownvotesPostAction extends PostsAction {
  protected function action(): ResponseInterface {
    $input = $this->getFormData();

    $userId = intval($this->request->getAttribute("userId"));

    if (!$userId) {
      return $this->respondWithData(["message" => "Operasi memerlukan kridenisal"], 401);
    }

    $post = $this->postsRepository->findOneByPostId($input->postId);

    if (!count($post)) {
      return $this->respondWithData(["message" => "post tidak ditemukan"], 404);
    }

    if ($userId !== $input->userId) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }

    $this->postsRepository->insertPostReaction($input->postId, $input->userId, -1);

    return $this->respondWithData(["message" => "postingan didownvote"]);
  }
}