<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org/vima-core>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Support\Mapping;

use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Dispatchers\DefaultEventDispatcher;

/**
 * Class MappingService
 * 
 * Handles persistent mapping between stable slugs and dynamic role/permission names.
 */
class MappingService
{
    private array $mapping = [
        'roles' => [],
        'permissions' => [],
        'namespaces' => []
    ];

    public function __construct(
        private string $mappingFilePath,
        private ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->dispatcher ??= new DefaultEventDispatcher();
        $this->load();
    }

    public function load(): void
    {
        if (file_exists($this->mappingFilePath)) {
            $content = file_get_contents($this->mappingFilePath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $this->mapping = array_merge($this->mapping, $data);
            }
        }
    }

    public function save(): void
    {
        $dir = dirname($this->mappingFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->mappingFilePath,
            json_encode($this->mapping, JSON_PRETTY_PRINT)
        );
    }

    public function getOrRegisterSlug(string $name, string $type): string
    {
        foreach ($this->mapping[$type] as $slug => $mappedName) {
            if ($mappedName === $name) {
                return $slug;
            }
        }

        $slug = $this->generateSlug($name);

        if (str_contains($name, ':')) {
            $namespace = explode(':', $name, 2)[0];
            $this->getOrRegisterNamespace($namespace);
        }

        $originalSlug = $slug;
        $counter = 1;
        while (isset($this->mapping[$type][$slug])) {
            $slug = $originalSlug . '_' . $counter++;
        }

        $this->mapping[$type][$slug] = $name;
        return $slug;
    }

    public function getOrRegisterNamespace(string $namespace): string
    {
        $slug = $this->generateSlug($namespace);

        if (!isset($this->mapping['namespaces'][$slug])) {
            $this->mapping['namespaces'][$slug] = $namespace;
        }

        return $slug;
    }

    public function sync(array $names, string $type): void
    {
        foreach ($names as $name) {
            $this->getOrRegisterSlug($name, $type);
        }
    }

    public function all(string $type): array
    {
        return $this->mapping[$type] ?? [];
    }

    private function generateSlug(string $name): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
        $slug = strtoupper($slug);
        $slug = preg_replace('/_+/', '_', $slug);
        return trim($slug, '_');
    }
}
