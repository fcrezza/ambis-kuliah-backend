<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Gump;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class GetUser extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    $this->logger->info("hit get user in user route");
    $this->logger->info("start validating input");
    $gump = new GUMP();
    $gump->filter_rules(["username" => ["sanitize_string"]]);
    $validParam = $gump->run(["username" => $this->resolveArg("username")]);
    $this->logger->info("done validating input");

    $this->logger->info("start getting user data for username $validParam[username]");
    $userData = $this->userRepository->getUserByUsername($validParam["username"]);

    if (!count($userData)) {
      throw new HttpNotFoundException($this->request, "Tidak ditemukan user dengan username $validParam[username]");
    }

    $this->logger->info("user found, start getting user avatar for username $validParam[username]");
    $userAvatar = $this->userRepository->getUserAvatar($userData["id"]);
    $this->logger->info("user found, done getting user avatar for username $validParam[username]");

    $this->logger->info("start getting user topics for username $validParam[username]");
    $userTopics = $this->userRepository->getUserTopics($userData["id"]);
    unset($userData["createdAt"]);
    $this->logger->info("done getting user topics for username $validParam[username]");

    $responseBody = array_merge($userData, ["avatar" => $userAvatar["url"], "topics" => $userTopics]);
    $this->logger->info("send successful response");
    return $this->respondWithData($responseBody);
  }
}
