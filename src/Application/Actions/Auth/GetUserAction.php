<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class GetUserAction extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
  }

  protected function action(): ResponseInterface {
    $this->logger->info('hit get user in auth route');
    $userId = $this->request->getAttribute("userId");

    if (!$userId) {
      $this->logger->info('user not authenticated, return empty object');
      return $this->respondWithData((object)[]);
    }

    $this->logger->info("start getting userId $userId data");
    $userData = $this->userRepository->getUserById($userId);
    $this->logger->info("done getting userId $userId data");

    $this->logger->info("start getting userId $userId topics");
    $userTopics = $this->userRepository->getUserTopics($userId);
    $this->logger->info("done getting userId $userId topics");

    $this->logger->info("start getting userId $userId avatar");
    $userAvatar = $this->userRepository->getUserAvatar($userId);
    $this->logger->info("done getting userId $userId avatar");
    unset($userData["createdAt"]);

    $this->logger->info("return successful respond to userId $userId");
    $responseBody = array_merge($userData, [
      "avatar" => $userAvatar["url"],
      "topics" => $userTopics
    ]);
    return $this->respondWithData($responseBody);
  }
}
