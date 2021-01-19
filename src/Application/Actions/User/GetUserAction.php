<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class GetUserAction extends Action {
  private $userRepository;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
      parent::__construct($logger);
      $this->userRepository = $userRepository;
  }

  protected function action(): Response {
    $username = $this->resolveArg("username");
    $userData = $this->userRepository->getUserByUsername($username);

    if (!$userData) {
      return $this->respondWithData(["message" => "data tidak ditemukan"], 404);
    }

    unset($userData["createdAt"]);
    $this->logger->info("hit user Profile route!");
    return $this->respondWithData($userData);
  }
}
