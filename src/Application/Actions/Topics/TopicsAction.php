<?php
declare(strict_types=1);

namespace App\Application\Actions\Topics;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\Topics\TopicsRepository;

class TopicsAction extends Action {
    protected $topicsRepository;

    public function __construct(LoggerInterface $logger, TopicsRepository $topicsRepository) {
        parent::__construct($logger);
        $this->topicsRepository = $topicsRepository;
    }

    protected function action(): Response {
      $userId = $this->request->getAttribute("userId");
      if (!$userId) {
        return $this->respondWithData(["message" => "Operasi memerlukan authentikasi!"], 401);
      }

      $topics = $this->topicsRepository->findAll();
      $this->logger->info("hit topics route!");
      return $this->respondWithData($topics);
    }
}