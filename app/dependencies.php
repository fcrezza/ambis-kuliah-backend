<?php
declare(strict_types=1);

use \PDO as PDO;
use Monolog\Logger;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use App\Domain\Token\Token;
use App\Infrastructure\ServiceToken;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    LoggerInterface::class => function (ContainerInterface $container) {
      $settings = $container->get('settings');

      $loggerSettings = $settings['logger'];
      $logger = new Logger($loggerSettings['name']);

      $processor = new UidProcessor();
      $logger->pushProcessor($processor);

      $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
      $logger->pushHandler($handler);

      return $logger;
    },
    PDO::class => function (ContainerInterface $container) {
      $settings = $container->get("settings");
      $database = $settings["database"];
      $host = $database["host"];
      $name = $database["name"];
      $username = $database["username"];
      $password = $database["password"];

      try {
        $conn = new PDO("mysql:host=$host;dbname=$name", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
      } catch (PDOException $e) {
        throw $e;
      }
    },
    Token::class => \DI\autowire(ServiceToken::class)
  ]);
};
