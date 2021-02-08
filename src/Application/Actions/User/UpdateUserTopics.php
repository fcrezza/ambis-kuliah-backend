<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Gump;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class UpdateUserTopics extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): ResponseInterface {
    $authenticatedUserId = $this->request->getAttribute("userId");

    if (!$authenticatedUserId) {
      throw new HttpUnauthorizedException($this->request, "Operasi memerlukan autentikasi");
    }

    $gump = new GUMP();
    $gump->validation_rules([
      "usernameParam" => ["required"],
      "topicIds" => ["required", "valid_array_size_greater" => 0],
      "topicIds.*" => ["integer"]
    ]);
    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "topicIds.*" => ["integer" => "Topic id bukan angka yang valid"],
    ]);
    $gump->filter_rules([
      "usernameParam" => ["sanitize_string"],
      "topicIds.*" => ["sanitize_numbers"]
    ]);
    $validInput = $gump->run(array_merge(["usernameParam" => $this->resolveArg("username")
    ], (array) $this->getFormData()));

    if (!$validInput) {
      $errors = $gump->get_errors_array();
      $firstErrorKey = array_key_first($errors);

      throw new HttpBadRequestException($this->request, $errors[$firstErrorKey]);
    }

    $user = $this->userRepository->getUserByUsername($validInput["usernameParam"]);

    if (!count($user)) {
      throw new HttpNotFoundException($this->request, "tidak ada user dengan username $validInput[usernameParam]");
    }

    if ($user["id"] !== $authenticatedUserId) {
      throw new HttpForbiddenException($this->request, "Operasi tidak diijinkan");
    }

    $userTopics = $this->userRepository->getUserTopics($user["id"]);

    if (!count($validInput["topicIds"]) && !count($userTopics)) {
      return $this->respondWithData([]);
    }

    if (!count($validInput["topicIds"]) && count($userTopics) >= 1) {
      $this->userRepository->withTransaction(function () use ($user, $userTopics) {
        foreach ($userTopics as $topic) {
          $this->userRepository->deleteUserTopic(["userId" => $user["id"], "topicId" => $topic["id"]]);
        }
      });

      return $this->respondWithData([]);
    }

    if (count($validInput["topicIds"]) >= 1 && !count($userTopics)) {
      $this->userRepository->withTransaction(function () use ($user, $validInput) {
        foreach ($validInput["topicIds"] as $topicId) {
          $this->userRepository->insertUserTopic(["userId" => $user["id"], "topicId" => $topicId]);
        }
      });
      $userTopics = $this->userRepository->getUserTopics($user["id"]);
      return $this->respondWithData($userTopics);
    }

    if (count($validInput["topicIds"]) >= 1 && count($userTopics) >= 1) {
      $addTopics = array_values(array_diff($validInput["topicIds"], array_column($userTopics, "id")));
      $deleteTopics= array_values(array_diff(array_column($userTopics, "id"), $validInput["topicIds"]));

      $this->userRepository->withTransaction(function () use ($user, $addTopics, $deleteTopics) {
        if (count($deleteTopics)) {
          foreach ($deleteTopics as $topicId) {
            $this->userRepository->deleteUserTopic(["userId" => $user["id"], "topicId" => $topicId]);
          }
        } elseif (count($addTopics)) {
          foreach ($addTopics as $topicId) {
            $this->userRepository->insertUserTopic(["userId" => $user["id"], "topicId" => $topicId]);
          }
        }
      });

      $userTopics = $this->userRepository->getUserTopics($user["id"]);
      return $this->respondWithData($userTopics);
    }

    return $this->respondWithData($userTopics);
  }
}
