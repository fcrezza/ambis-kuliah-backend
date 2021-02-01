<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use \Cloudinary;
use \Cloudinary\Uploader;

use App\Application\Actions\Posts\PostsAction;

class DeleteUserPostAction extends PostsAction {
  protected function action(): ResponseInterface {
    $authorUsernameParam = $this->resolveArg('authorUsername');
    $postIdParam = intval($this->resolveArg('postId'));

    $userId = $this->request->getAttribute("userId");

    if (!$userId) {
      return $this->respondWithData(["message" => "Operasi memerlukan kridenisal"], 401);
    }


    $user = $this->userRepository->getUserByUsername($authorUsernameParam);

    if (!$user) {
      return $this->respondWithData(["message" => "Tidak ditemukan user dengan username $authorUsernameParam"], 404);
    }

    $post = $this->postsRepository->findOneByPostId($postIdParam);

    if (!count($post)) {
      return $this->respondWithData(["message" => "post tidak ditemukan"], 404);
    }

    if ($userId !== intval($user["id"])) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }

    $postImage = $this->postsRepository->findImageByPostId($postIdParam);

    if (count($postImage)) {
        Cloudinary::config([
          "cloud_name" => $_ENV["CLOUDINARY_CLOUD"],
          "api_key" => $_ENV["CLOUDINARY_KEY"],
          "api_secret" => $_ENV["CLOUDINARY_SECRET"]
        ]);

        Uploader::destroy($postImage["publicId"], ["invalidate" => true]);
    }

    $this->postsRepository->deletePost($postIdParam);

    return $this->respondWithData(["message" => "Berhasil menghapus postingan"]);
  }
}