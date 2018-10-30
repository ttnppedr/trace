<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HttpKernelInterface handles a Request to convert it to a Response.
 * HttpKernelInterface 處理 Request 轉換到 Response
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface HttpKernelInterface
{
    // MASTER_REQUEST 是外部原始來的
    // SUB_REQUEST 是內部自己發起的，如 controller 中再 forward
    const MASTER_REQUEST = 1;
    const SUB_REQUEST = 2;

    /**
     * Handles a Request to convert it to a Response.
     * 處理 Request 轉換到  Response
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     * 當 $catch 為 true ，實作就必需捕捉所有的 exception 並盡力轉換到 Response
     *
     * @param Request $request A Request instance
     * @param int     $type    The type of the request
     *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param bool    $catch   Whether to catch exceptions or not
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true);
}
