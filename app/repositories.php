<?php
declare(strict_types=1);

use DI\ContainerBuilder;

use App\Domain\Topics\TopicsRepository;
use App\Infrastructure\ServiceTopicsRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        TopicsRepository::class => \DI\autowire(ServiceTopicsRepository::class),
        UserRepository::class => \DI\autowire(ServiceUserRepository::class)
    ]);
};
