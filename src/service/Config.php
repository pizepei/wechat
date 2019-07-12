<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/12
 * Time: 17:36
 * @title 获取配置类
 */

namespace pizepei\wechat\service;


use pizepei\wechat\model\OpenWechatConfigModel;

class Config
{
    /**
     * @var null
     */
    protected $redis = null;





    /**
     * Config constructor.
     * @param \Redis $redis 缓存
     */
    public function __construct(\Redis $redis)
    {



    }

    /**
     * 获取开放平台配置
     * @param bool $cache
     * @param string $appid
     */
    public function getOpenConfig($cache=true,$appid='')
    {
        /**
         * 判断是否需要缓存
         */
        if ($cache){
            $OpenWechatConfigModel = OpenWechatConfigModel::table();
            $config =  $OpenWechatConfigModel->limit();
        }else{
            $OpenWechatConfigModel = OpenWechatConfigModel::table();
            $config =  $OpenWechatConfigModel->where(['appid'=>$appid])->fetch();
        }
        return $config;

    }
    /**
     * 获取开发模式微信公众号配置
     * @param bool $cache
     */
    public function getAloneConfig($cache=true,$appid='')
    {
        /**
         * 判断是否需要缓存
         */
    }

}