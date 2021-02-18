<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    'settings' => [
      'displayErrorDetails' => getenv("APP_ENV") === "DEVELOPMENT" ? true : false, // Should be set to false in production
      'logger' => [
        'name' => 'ambis-kuliah',
        'path' => getenv('docker') ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'level' => Logger::DEBUG,
      ],
      "database" => [
        "host" => getenv("APP_ENV") === "PRODUCTION" ? getenv("MYSQL_ADDON_HOST") : getenv("DATABASE_HOST"),
        "name" => getenv("APP_ENV") === "PRODUCTION" ? getenv("MYSQL_ADDON_DB") : getenv("DATABASE_NAME"),
        "username" => getenv("APP_ENV") === "PRODUCTION" ? getenv("MYSQL_ADDON_USER") : getenv("DATABASE_USERNAME"),
        "password" => getenv("APP_ENV") === "PRODUCTION" ? getenv("MYSQL_ADDON_PASSWORD") : getenv("DATABASE_PASSWORD")
      ]
    ],
  ]);
};
