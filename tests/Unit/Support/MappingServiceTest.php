<?php

namespace Vima\Core\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Vima\Core\Support\Mapping\MappingService;

class MappingServiceTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vima_test_mapping_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testGenerateTypeScriptFiles()
    {
        $service = new MappingService($this->tempFile);
        $service->getOrRegisterSlug('admin', 'roles');
        $service->getOrRegisterSlug('post.create', 'permissions');
        $service->getOrRegisterNamespace('tenant_1');
        $service->save();

        $tsDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vima_ts_test_' . uniqid();
        $service->generateTypeScriptFiles($tsDir);

        $this->assertFileExists($tsDir . DIRECTORY_SEPARATOR . 'Roles.ts');
        $this->assertFileExists($tsDir . DIRECTORY_SEPARATOR . 'Permissions.ts');
        $this->assertFileExists($tsDir . DIRECTORY_SEPARATOR . 'Namespaces.ts');

        $rolesContent = file_get_contents($tsDir . DIRECTORY_SEPARATOR . 'Roles.ts');
        $this->assertStringContainsString("export const Roles = {", $rolesContent);
        $this->assertStringContainsString("ADMIN: 'admin'", $rolesContent);

        // Clean up TS dir
        foreach (['Roles.ts', 'Permissions.ts', 'Namespaces.ts'] as $file) {
            unlink($tsDir . DIRECTORY_SEPARATOR . $file);
        }
        rmdir($tsDir);
    }
}
