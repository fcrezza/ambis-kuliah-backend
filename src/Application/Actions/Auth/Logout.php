<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class Logout extends Action {
   private $container;

    protected $userRepository;

    public function __construct(LoggerInterface $logger, UserRepository $userRepository) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
    }

    protected function action(): ResponseInterface {
      $data = $this->userRepository->findAll();
      $this->logger->info("hit login route!");
      return $this->respondWithData($topics);
    }
}