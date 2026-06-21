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

namespace Vima\Core;

use Vima\Core\Support\Discovery\Container;

if (!function_exists('Vima\Core\container')) {
    /**
     * Get the Dependency Container instance.
     *
     * @return Container
     */
    function container(): Container
    {
        return Container::getInstance();
    }
}

if (!function_exists('Vima\Core\resolve')) {
    /**
     * Resolve a dependency from the container.
     *
     * @param string $id
     * @return object
     */
    function resolve(string $id): object
    {
        return container()->get($id);
    }
}

if (!function_exists('Vima\Core\make')) {
    /**
     * Alias for resolve().
     *
     * @param string $id
     * @return object
     */
    function make(string $id): object
    {
        return resolve($id);
    }
}

if (!function_exists('Vima\Core\register')) {
    /**
     * Register a binding in the container.
     *
     * @param string|object $abstract
     * @param object|string|null $concrete
     * @return void
     */
    function register(string|object $abstract, object|string|null $concrete = null): void
    {
        container()->register($abstract, $concrete);
    }
}

if (!function_exists('Vima\Core\registerMany')) {
    /**
     * Register multiple bindings in the container.
     *
     * @param array $dependencies
     * @return void
     */
    function registerMany(array $dependencies): void
    {
        container()->registerMany($dependencies);
    }
}

if (!function_exists('Vima\Core\singleton')) {
    /**
     * Bind a singleton instance to an abstract.
     *
     * @param string $abstract
     * @param object $instance
     * @return void
     */
    function singleton(string $abstract, object $instance): void
    {
        container()->bind($abstract, $instance);
    }
}
