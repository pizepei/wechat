<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/13 15:53
 * @title 模块基础类
 */

namespace pizepei\wechat\module;


use pizepei\wechat\basics\ReplyApi;

class BaseModule
{
    /**
     * @var null
     */
    public $obj = null;

    /**
     * BaseModule constructor.
     * @param ReplyApi $obj
     */
    public function __construct(ReplyApi $obj)
    {
        $this->obj = $obj;
    }
}