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

class InsertUserTopics extends Action {
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
      "topicIds" => ["required", "valid_array_size_greater" => 1],
      "topicIds.*" => ["integer"]
    ]);
    $gump->set_fields_error_messages([
      "usernameParam" => ["required" => "Parameter username tidak boleh kosong"],
      "topicIds" => ["required" => "Topic Ids tidak boleh kosong", "valid_array_size_greater" => "Topic Ids tidak boleh kosong"],
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

    $this->userRepository->withTransaction(function () use ($validInput, $user) {
      foreach ($validInput["topicIds"] as $topicId) {
        $this->userRepository->insertUserTopic(["userId" => $user["id"], "topicId" => $topicId]);
      }
    });

    $userTopics = $this->userRepository->getUserTopics($user["id"]);

    return $this->respondWithData($userTopics);
  }
}
