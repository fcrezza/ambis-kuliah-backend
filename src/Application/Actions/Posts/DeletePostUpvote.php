<?php
declare(strict_types=1);

namespace App\Application\Actions\Posts;

use Gump;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Domain\Posts\PostsRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;

class DeletePostUpvote extends Action {
  private $postsRepository;

  public function __construct(LoggerInterface $logger, PostsRepository $postsRepository) {
    parent::__construct($logger);
    $this->postsRepository = $postsRepository;
  }

  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException($this->request, "Operasi ini memerlukan authentikasi");
    }

    $gump = new GUMP();

    $gump->validation_rules([
      "postId" => ["required", "integer"],
      "idUser" => ["required", "integer"],
    ]);

    $gump->set_fields_error_messages([
      "postId" => ["required" => "postId tidak boleh kosong", "integer" => "Parameter postId tidak valid"],
      "idUser" => ["required" => "userId tidak boleh kosong", "integer" => "Parameter userId tidak valid"],
    ]);

    $gump->filter_rules([
      "postId" => ["sanitize_numbers"],
      "idUser" => ["sanitize_numbers"],
    ]);

    $validInput = $gump->run(["idUser" => $this->resolveArg("idUser"), "postId" => $this->resolveArg("postId")]);

    if (!is_array($validInput)) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $post = $this->postsRepository->getPostByPostId(intval($validInput["postId"]));

    if (!count($post)) {
      throw new HttpNotFoundException($this->request, "Tidak ada post dengan id $validInput[postId]");
    }

    if ($authenticatedUserId !== intval($validInput["idUser"])) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $this->postsRepository->deletePostVote(["postId" => intval($validInput["postId"]), "userId" => intval($validInput["idUser"]), "type" => 1]);

    return $this->respondWithData(["message" => "success"]);
  }
}
