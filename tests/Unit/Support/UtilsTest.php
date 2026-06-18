<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Vima\Core\Support\Utils\Utils;

class UtilsTest extends TestCase
{
    public function testResolveNamespaceSplitsCorrectly()
    {
        $result = Utils::resolveNamespace('tenant_1:posts.edit');
        $this->assertEquals(['tenant_1', 'posts.edit'], $result);
    }

    public function testResolveNamespaceWithoutNamespace()
    {
        $result = Utils::resolveNamespace('posts.edit');
        $this->assertEquals([null, 'posts.edit'], $result);
    }

    public function testSortRecursive()
    {
        $input = [
            'z' => 1,
            'a' => [
                'c' => 2,
                'b' => 3
            ]
        ];

        $expected = [
            'a' => [
                'b' => 3,
                'c' => 2
            ],
            'z' => 1
        ];

        $result = Utils::sortRecursive($input);
        $this->assertEquals($expected, $result);
    }
}
