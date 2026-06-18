<?php

declare(strict_types=1);

namespace Vima\Core\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Vima\Core\Support\Discovery\Container;
use Vima\Core\Support\Discovery\CoreBootstrapper;

use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Permission\Contracts\PermissionRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;

use Vima\Core\Tests\Fixtures\MockRepositories\ArrayRoleRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayPermissionRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayRolePermissionRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayRoleParentRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayUserRoleRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayUserPermissionRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayUserDenyRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayUserRoleDenyRepository;
use Vima\Core\Tests\Fixtures\MockRepositories\ArrayAuditRepository;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();
        $this->container = Container::getInstance();

        $this->registerMockRepositories();

        CoreBootstrapper::bootstrap($this->container);
    }

    protected function registerMockRepositories(): void
    {
        $this->container->register(RoleRepositoryInterface::class, new ArrayRoleRepository());
        $this->container->register(PermissionRepositoryInterface::class, new ArrayPermissionRepository());
        $this->container->register(RolePermissionRepositoryInterface::class, new ArrayRolePermissionRepository());
        $this->container->register(RoleParentRepositoryInterface::class, new ArrayRoleParentRepository());
        $this->container->register(UserRoleRepositoryInterface::class, new ArrayUserRoleRepository());
        $this->container->register(UserPermissionRepositoryInterface::class, new ArrayUserPermissionRepository());
        $this->container->register(UserDenyRepositoryInterface::class, new ArrayUserDenyRepository());
        $this->container->register(UserRoleDenyRepositoryInterface::class, new ArrayUserRoleDenyRepository());
        $this->container->register(\Vima\Core\Audit\Contracts\AuditRepositoryInterface::class, new ArrayAuditRepository());
    }
}
