<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;

class Logout extends Action {
    public function __construct(LoggerInterface $logger) {
        parent::__construct($logger);
    }

    protected function action(): ResponseInterface {
      setcookie("accessToken", "", time() - 3600, '/', 'localhost', false, true);
      setcookie("refreshToken", "", time() - 3600, '/', 'localhost', false, true);
      $this->logger->info("hit logout route");
      return $this->respondWithData(["message" => "berhasil logout"]);
    }
}