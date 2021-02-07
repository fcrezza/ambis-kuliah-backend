<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    'settings' => [
      'displayErrorDetails' => $_ENV["APP_ENV"] === "DEVELOPMENT" ? true : false, // Should be set to false in production
      'logger' => [
        'name' => 'ambis-kuliah',
        'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'level' => Logger::DEBUG,
      ],
      "database" => [
        "host" => $_ENV["DATABASE_HOST"],
        "name" => $_ENV["DATABASE_NAME"],
        "username" => $_ENV["DATABASE_USERNAME"],
        "password" => $_ENV["DATABASE_PASSWORD"]
      ]
    ],
  ]);
};
