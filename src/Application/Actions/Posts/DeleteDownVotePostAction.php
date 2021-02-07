<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Posts\PostsAction;

class DeleteDownvotePostAction extends PostsAction {
  protected function action(): ResponseInterface {
    $postIdParam = intval($this->resolveArg("postId"));
    $userIdParam = intval($this->resolveArg("idUser"));

    $userId = intval($this->request->getAttribute("userId"));

    if (!$userId) {
      return $this->respondWithData(["message" => "Operasi memerlukan kridenisal"], 401);
    }

    $post = $this->postsRepository->findOneByPostId($postIdParam);

    if (!count($post)) {
      return $this->respondWithData(["message" => "post tidak ditemukan"], 404);
    }

    if ($userId !== $userIdParam) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }

    $this->postsRepository->deletePostReaction($postIdParam, $userIdParam, -1);

    return $this->respondWithData(["message" => "berhasil batal downvote"]);
  }
}
