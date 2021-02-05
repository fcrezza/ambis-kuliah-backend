<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Posts\PostsAction;
use Gump;
use \Cloudinary;
use \Cloudinary\Uploader;

class InsertPostReplyAction extends PostsAction {
  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");
    $authorUsernameParam = $this->resolveArg("authorUsername");
    $postIdParam = intval($this->resolveArg("postId"));

    if (!$authenticatedUserId) {
      return $this->respondWithData(["message" => "Operasi memerlukan autentikasi"], 401);
    }

    $gump = new GUMP();

    $gump->validation_rules([
      "replyContent" => ["required"],
      "userId" => ["required", "integer"],
      "image" => ["extension" => "png;jpg;jpeg;gif"],
    ]);

    $gump->set_fields_error_messages([
      "replyContent" => ["required" => "Isi komentar tidak boleh kosong"],
      "userId" => ["required" => "user id tidak boleh kosong", "integer" => "user id bukan valid integer"],
      "image" => ["extension" => "Ekstensi file tidak didukung"]
    ]);

    $gump->filter_rules([
      "replyContent" => ["trim", "sanitize_string"],
      "userId" => ["sanitize_numbers"]
    ]);

    $valid_input = $gump->run(array_merge($_POST, $_FILES));

    if (!$valid_input) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      return $this->respondWithData(["name" => $firstErrorKey, "message" => $errors[$firstErrorKey]], 403);
    }

    $user = $this->userRepository->getUserById(intval($valid_input["userId"]));

    if (intval($user["id"]) !== $authenticatedUserId) {
      return $this->respondWithData(["message" => "Operasi tidak diijinkan"], 403);
    }

    if (isset($_FILES["image"])) {
      Cloudinary::config([
        "cloud_name" => $_ENV["CLOUDINARY_CLOUD"],
        "api_key" => $_ENV["CLOUDINARY_KEY"],
        "api_secret" => $_ENV["CLOUDINARY_SECRET"]
      ]);
      $res = Uploader::upload($_FILES["image"]["tmp_name"], ["folder" => "ambiskuliah/posts"]);
    }

    $payload = [
      "user" => ["id" => $authenticatedUserId],
      "post" => [
        "id" => $postIdParam,
        "description" => $valid_input["replyContent"],
      ]
    ];

    if (isset($valid_input["description"])) {
      $payload["post"]["description"] = $valid_input["description"];
    }

    if (isset($res)) {
      $payload["post"]["image"] = [
        "publicId" => stripslashes($res["public_id"]),
        "url" => stripslashes($res["secure_url"])
      ];
    }

    $this->postsRepository->insertPostReply($payload);

    return $this->respondWithData(["message" => "Sukses membalas postingan"]);
  }
}
