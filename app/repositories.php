<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use App\Domain\Topics\TopicsRepository;
use App\Infrastructure\ServiceTopicsRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\ServiceUserRepository;
use App\Domain\Posts\PostsRepository;
use App\Infrastructure\ServicePostsRepository;
use App\Domain\Token\TokenRepositoryInterface;
use App\Infrastructure\TokenRepository;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    TopicsRepository::class => \DI\autowire(ServiceTopicsRepository::class),
    UserRepository::class => \DI\autowire(ServiceUserRepository::class),
    PostsRepository::class => \DI\autowire(ServicePostsRepository::class),
    TokenRepositoryInterface::class => \DI\autowire(TokenRepository::class)
  ]);
};

/**
 * TODO:
 * implement token repository
 */
