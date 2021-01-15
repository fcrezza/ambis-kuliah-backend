<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class UpdateUserAction extends Action {
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

      $input = $this->getFormData();
      $assocInput = array_combine($input->data, $input->data);

      if ($input->name === "topics") {
        $userTopics = $this->userRepository->getUserTopics((int) $userId);
        $addedTopics = $input->data;
        $deletedTopics= [];

        if (!count($userTopics)) {
          $this->userRepository->updateUserTopics((int) $userId, $addedTopics, $deletedTopics);
          $userTopics = $this->userRepository->getUserTopics((int) $userId);
          return $this->respondWithData($userTopics);
        }

        foreach ($userTopics as $topic) {
          // found in db but not in input
          if (!isset($assocInput[$topic["id"]])) {
            $deletedTopics[] = $topic["id"];
            $input->data = array_diff($input->data, [$topic["id"]]);
          } else if (isset($assocInput[$topic["id"]])) { // found in db and found in input, ignore
            $addedTopics = array_diff($addedTopics, [$topic["id"]]);
          }
        }

        $this->userRepository->updateUserTopics((int) $userId, $addedTopics, $deletedTopics);
        $userTopics = $this->userRepository->getUserTopics((int) $userId);

        return $this->respondWithData($userTopics);
      }


      // $hashedPassword = password_hash($input->password, PASSWORD_DEFAULT);
      // return $this->respondWithData(["message" => "password berhasil diubah"]);
      // $userData = $this->userRepository->updateUserProfile($input);
      // $userTopics = $this->userRepository->getUserTopics((int) $userId);
      // $responseBody = array_merge($userData, ["topics" => $userTopics]);
      // return $this->respondWithData($input);
    }
}