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
      $userId = $this->request->getAttribute("userId");

      if (!$userId) {
        return $this->respondWithData(["message" => "Operasi memerlukan authentikasi!"], 401);
      }

      $userData = $this->userRepository->getUserById((int) $userId);
      $userTopics = $this->userRepository->getUserTopics((int) $userId);
      $responseBody = array_merge($userData, ["topics" => $userTopics]);
      return $this->respondWithData($responseBody);
    }
}