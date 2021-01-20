<?php
declare(strict_types=1);

namespace App\Application\Actions\Topics;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\Topics\TopicsRepository;

class GetTopicsAction extends Action {
    protected $topicsRepository;

    public function __construct(LoggerInterface $logger, TopicsRepository $topicsRepository) {
        parent::__construct($logger);
        $this->topicsRepository = $topicsRepository;
    }

    protected function action(): Response {
      $topics = $this->topicsRepository->findAll();
      $this->logger->info("hit topics route!");
      return $this->respondWithData($topics);
    }
}