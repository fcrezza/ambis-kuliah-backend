<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Psr\Http\Message\ResponseInterface;
use App\Application\Actions\Posts\PostsAction;
use Gump;
use \Cloudinary;
use \Cloudinary\Uploader;

class PostUserPostAction extends PostsAction {
  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");
    $authorUsernameParam = $this->resolveArg("authorUsername");

    if (!$authenticatedUserId) {
      return $this->respondWithData(["message" => "Operasi memerlukan autentikasi"], 401);
    }

    $gump = new GUMP();
    $decodedPost = array_merge($_POST, ["topics" => json_decode($_POST["topics"])]);

    $gump->validation_rules([
      "title" => ["required", "alpha_numeric_space"],
      "topics" => ["required", "valid_array_size_greater" => 1, "valid_array_size_lesser" => 3],
      "topics.*" => ["integer"],
      "image" => ["extension" => "png;jpg;jpeg;gif"],
    ]);

    $gump->set_fields_error_messages([
      "title" => ["required" => "Judul post tidak boleh kosong"],
      "topics" => ["required" => "Post minimal memiliki 1 topik"],
      "topics.*" => ["integer" => "topic id tidak valid"],
      "image" => ["extension" => "Ekstensi file tidak didukung"]
    ]);

    $gump->filter_rules([
      "title" => ["trim", "sanitize_string"],
      "description" => ["trim", "sanitize_string"],
      "topics.*" => ["sanitize_numbers"]
    ]);

    $valid_input = $gump->run(array_merge($decodedPost, $_FILES));

    if (!$valid_input) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      return $this->respondWithData(["name" => $firstErrorKey, "message" => $errors[$firstErrorKey]], 403);
    }

    $user = $this->userRepository->getUserByUsername($authorUsernameParam);

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
        "title" => $valid_input["title"],
        "topics" => $valid_input["topics"],
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

    // return $this->respondWithData($valid_input);
    $this->postsRepository->insertPost($payload);

    return $this->respondWithData(["message" => "success membuat postingan"]);
  }
}
