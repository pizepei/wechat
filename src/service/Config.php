<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/12
 * Time: 17:36
 * @title 获取配置类
 */

namespace pizepei\wechat\service;


use http\Message\Body;
use pizepei\model\redis\Redis;
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
     * @param bool $cache 是否刷新缓存
     * @param string $appid
     */
    public function getOpenConfig($cache=false,$appid='')
    {
        # 判断是否需要刷新缓存
        if ($cache){
            return $config = $this->coerceGetOpenConfig($cache,$appid);
        }else{
            $config = $this->redis->get(self::Open_cache_prefix.$appid);
            $config = json_decode($config,true);
            if (!empty($config) && $config !==null   && $config!==false && is_array($config)){
                return $config;
            }else{
                $config = $this->coerceGetOpenConfig($cache,$appid);
                return $config;
            }
        }
        return $config;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/26 13:47
     * @param $cache
     * @param $appid
     * @title  强制获取开放平台配置
     * @return array|mixed
     * @throws \Exception
     */
    protected function coerceGetOpenConfig(string $cache,string$appid)
    {
        $OpenWechatConfigModel = OpenWechatConfigModel::table();
        if ($appid == ''){
            $config =  $OpenWechatConfigModel->limit()[0]??[];
        }else{
            $config =  $OpenWechatConfigModel->where(['appid'=>$appid])->fetch();
        }
        if (!empty($config)){
            $this->redis->set(self::Open_cache_prefix.$appid,json_encode($config),self::Alone_cache_time);
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
     * @param bool $cache 是否强制刷新缓存
     * @param bool $appid appid
     */
    public function getAloneConfig($cache=false,$appid)
    {
        /**
         * 判断是否需要缓存  true是强制刷新
         * 注意公众号配置的缓存是在这里缓存   平台配置缓存是getOpenConfig（）方法里面
         */
        if ($cache){
            # 强制刷新缓存
            $config = $this->coerceGetAloneConfig($cache,$appid);
        }else{
            # 读取缓存
            $config = $this->redis->get(self::Alone_cache_prefix.$appid);
            $config = json_decode($config,true);
            # 判断缓存是否正确
            if (empty($config) || $config ==null   || $config ==false || !is_array($config)){
                # 重新获取
                $config = $this->coerceGetAloneConfig($cache,$appid);
            }
        }
        # 获取平台配置
        $OpenConfig = $this->getOpenConfig($cache,$config['component_appid']);
        if (!isset($OpenConfig['appid'])){
            throw new \Exception('获取OpenConfig失败');
        }
        $config['EncodingAESKey'] = $OpenConfig['EncodingAESKey'];
        $config['token'] = $OpenConfig['token'];
        $config['open_domain'] = $OpenConfig['open_domain'];
        $config['appid'] = $OpenConfig['appid'];
        $config['cache_prefix'] = $OpenConfig['cache_prefix'];
        $config['appsecret'] = $OpenConfig['appsecret'];
        return $config;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/26 13:47
     * @param $cache
     * @param $appid
     * @title  强制获取公众号配置
     * @return array|mixed
     * @throws \Exception
     */
    protected function coerceGetAloneConfig(bool $cache,$appid)
    {
        $config = OpenAuthorizerUserInfoModel::table()->where(['authorizer_appid'=>$appid])->fetch();
        if (!empty($config)){
            $this->redis->set(self::Alone_cache_prefix.$appid,json_encode($config),self::Alone_cache_time);
        }
        return $config;
    }



    /**
     * @Author pizepei
     * @Created 2019/3/3 13:23
     *
     * @param null $authorizerAppid
     * @param null $authorizerRefreshToken
     * @param bool $restart
     * @return string
     * @throws \Exception
     *
     * @title  access_token 获取
     * @explain $restart=false 使用缓存   true 刷新缓存
     */
    public function access_token($authorizerAppid,$restart=false)
    {
        $config = $this->getAloneConfig($restart,$authorizerAppid);
        Open::init($config,$this->redis);
        $access_token = Open::authorizer_access_token($config['authorizer_appid'],$config['authorizer_refresh_token'],$restart);
        if (isset($access_token['authorizer_access_token'])){
            return $access_token;
        }else{
            throw new \Exception('获取authorizer_access_token失败');
        }
    }
}