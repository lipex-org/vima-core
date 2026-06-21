<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Vima\Core\Config\Schema\Tables;
use Vima\Core\Config\VimaConfig;

class VimaConfigTest extends TestCase
{
    public function testTablesSchemaAppliesPrefixAutomatically()
    {
        $tables = new Tables(prefix: 'sys_');

        $this->assertEquals('sys_roles', $tables->roles);
        $this->assertEquals('sys_permissions', $tables->permissions);
        $this->assertEquals('sys_user_denies', $tables->userDenies);
    }

    public function testTablesSchemaAllowsManualOverridesDespitePrefix()
    {
        $tables = new Tables(prefix: 'sys_', roles: 'custom_roles_table');

        $this->assertEquals('custom_roles_table', $tables->roles);
        $this->assertEquals('sys_permissions', $tables->permissions); // Falls back to prefix
    }

    public function testVimaConfigInstantiatesWithDefaults()
    {
        $config = new VimaConfig();

        $this->assertFalse($config->cacheEnabled);
        $this->assertEquals(3600, $config->cacheTTL);
        $this->assertTrue($config->superAdminBypass);
        $this->assertEquals('vima_roles', $config->tables->roles); // Default vima_ prefix
    }
}
