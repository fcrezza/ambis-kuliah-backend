<?php
declare(strict_types=1);

namespace App\Application\Handlers;

use Psr\Log\LoggerInterface;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;
use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;

class HttpErrorHandler extends SlimErrorHandler {
  protected $logger;

  public function __construct($callableResolver, $responseFactory, LoggerInterface $logger) {
    parent::__construct($callableResolver, $responseFactory);
    $this->logger = $logger;
  }

  /**
   * @inheritdoc
   */
  protected function respond(): Response {
    $exception = $this->exception;
    $statusCode = 500;
    $error = new ActionError(
      ActionError::SERVER_ERROR,
      'An internal error has occurred while processing your request.'
    );

    if ($exception instanceof HttpException) {
      $statusCode = $exception->getCode();
      $errorMessage = $exception->getMessage();
      $error->setMessage($errorMessage);
      $this->logger->error($errorMessage);

      if ($exception instanceof HttpNotFoundException) {
        $error->setType(ActionError::RESOURCE_NOT_FOUND);
      } elseif ($exception instanceof HttpMethodNotAllowedException) {
        $error->setType(ActionError::NOT_ALLOWED);
      } elseif ($exception instanceof HttpUnauthorizedException) {
        $error->setType(ActionError::UNAUTHENTICATED);
      } elseif ($exception instanceof HttpForbiddenException) {
        $error->setType(ActionError::INSUFFICIENT_PRIVILEGES);
      } elseif ($exception instanceof HttpBadRequestException) {
        $error->setType(ActionError::BAD_REQUEST);
      } elseif ($exception instanceof HttpNotImplementedException) {
        $error->setType(ActionError::NOT_IMPLEMENTED);
      }
    }

    if (
            !($exception instanceof HttpException)
            && $exception instanceof Throwable
            && $this->displayErrorDetails
        ) {
      $errorMessage = $exception->getMessage();
      $error->setMessage($errorMessage);
      $this->logger->critical($errorMessage);
    }
    $payload = new ActionPayload($statusCode, null, $error);
    $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT);

    $response = $this->responseFactory->createResponse($statusCode);
    $response->getBody()->write($encodedPayload);

    return $response->withHeader('Content-Type', 'application/json');
  }
}
