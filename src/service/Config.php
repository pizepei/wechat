<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/12
 * Time: 17:36
 * @title 获取配置类
 */

namespace pizepei\wechat\service;


use pizepei\wechat\model\OpenAuthorizerUserInfoModel;
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
        $this->redis = $redis;
    }
    /**
     * 缓存时间单位s
     */
    const Open_cache_time = 120;
    /**
     * 缓存prefix
     */
    const Open_cache_prefix = 'wechat:Alone:Config:';
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
            $config = $this->redis->get(self::Open_cache_prefix.$appid);
            $config = json_decode($config,true);
            if (!empty($config) || $config !==null   || $config!==false || is_array($config)){
                return $config;
            }
        }
        $OpenWechatConfigModel = OpenWechatConfigModel::table();
        if ($appid == ''){
            $config =  $OpenWechatConfigModel->limit();
        }else{
            $config =  $OpenWechatConfigModel->where(['appid'=>$appid])->fetch();
        }
        if ($cache && !empty($config)){
            $this->redis->set(self::Alone_cache_prefix.$appid,json_encode($config),self::Alone_cache_time);
        }

        return $config;

    }
    /**
     * 缓存时间单位s
     */
    const Alone_cache_time = 120;
    /**
     * 缓存prefix
     */
    const Alone_cache_prefix = 'wechat:Alone:Config:';

    /**
     * 获取开发模式微信公众号配置
     * @param bool $cache
     */
    public function getAloneConfig($cache=false,$appid)
    {
        /**
         * 判断是否需要缓存
         */
        if ($cache){
            $config = $this->redis->get(self::Alone_cache_prefix.$appid);
            $config = json_decode($config,true);
        }
        if (!empty($config) || $config !==null   || $config!==false || is_array($config)){
            return $config;
        }
        /**
         * 没有获取
         */
        $config = OpenAuthorizerUserInfoModel::table()->where(['authorizer_appid'=>$appid])->fetch();
        if ($cache && !empty($config)){
            $this->redis->set(self::Alone_cache_prefix.$appid,json_encode($config),self::Alone_cache_time);
        }
        return $config;
    }

}