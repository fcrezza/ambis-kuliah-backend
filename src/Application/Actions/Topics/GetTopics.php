<?php
declare(strict_types=1);

namespace App\Application\Actions\Topics;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Action;
use App\Domain\TopicsRepositoryInterface;

class GetTopics extends Action {
  protected $topicsRepository;

  public function __construct(LoggerInterface $logger, TopicsRepositoryInterface $topicsRepository) {
    parent::__construct($logger);
    $this->topicsRepository = $topicsRepository;
  }

  protected function action(): Response {
    $this->logger->info("hit topics route");
    $this->logger->info("start getting topics data");
    $topics = $this->topicsRepository->getAllTopics();
    $this->logger->info("done getting topics data");
    $this->logger->info("sending successful response");
    return $this->respondWithData($topics);
  }
}
