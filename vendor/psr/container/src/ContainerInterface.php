<?php
/**
 * @license http://www.opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 */

namespace Psr\Container;

/**
 * Describes the interface of a container that exposes methods to read its entries.
 * 描述容器的 interface ，可以露出讀取 entries 的方法
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     * 用 id 找出 container 的入口並回傳
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     * 回傳 ture ，如果容器可以回傳此 id 的入口
     * 否則回傳 false
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     * has 回傳 true ，不代表 get 就不會丟 exception
     * 只是代表 get 不會丟出'找不到的 exception'
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id);
}
