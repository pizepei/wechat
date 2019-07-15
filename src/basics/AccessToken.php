<?php
/**
 * @Author: pizepei
 * @Date:   2017-06-03 14:39:36
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-06-28 16:34:13
 */
namespace pizepei\wechat\basics;

use pizepei\wechat\service\Open;
use jt\error\Exception;
use utils\wechatbrief\Config;
use utils\wechatbrief\RedisModel;
use utils\wx\common\WechatBase;


/**
 * 获取access_token
 */
class AccessToken
{

    /**
     * 配置信息
     * @var array|null
     */
     protected $config = [];//配置信息
    /**
     * redis对象
     * @var null
     */
     protected $redis = null;//redis
     protected $access_token = '';//access_token
     protected $expires_time = 3600;//expires_time

    /**
     * AccessToken constructor.
     *构造函数，获取Access Token
     * @param null                         $config
     * @param  $redis
     */
     public function __construct($config,$redis)
     {
         /**
          * 初始化配置
          */
         $this->config = $config;
         /**
          * 初始化redis缓存
          */
         $this->redis = $redis;
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
     * @explain 根据配置文件判断模式，$restart=false 时强制获取
     */
     public function access_token($authorizerAppid = null,$authorizerRefreshToken = null,$restart=true){

        /**
        * 判断是否是第三方开发模式
         * hird 第三方  tradition 传统模式
        */
        if($this->config['pattern'] === 'third')
        {
            Open::init($this->config,$this->redis);
            $this->access_token = Open::authorizer_access_token($authorizerAppid,$authorizerRefreshToken,$restart)['authorizer_access_token'];
        }
        else if($this->config['pattern'] === 'tradition')
        {
            $this->tradition_access_token();
        }
        return $this->access_token;
     }
    /**
     * [get_access_token 获取数据]
     * @Effect
     * @param  [type] $url  [description]
     * @param  [type] $data [description]
     * @param  [type] $restart [description]
     * @return [type]       [description]
     */
    protected function tradition_access_token($restart=false)
    {

        $data = $this->redis->get($this->config['prefix'].'access_token_'.$this->config['appid']);
        if(empty($data) && $restart){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->config['appid']."&secret=".$this->config['appsecret'];
            $res = Func::http_request($url);
            $access_token = json_decode($res, true);
            if(isset($access_token['errcode'])){
                throw new Exception($access_token['errmsg']);
            }
            $this->redis->set($this->config['prefix'].'access_token_'.$this->config['appid'],$access_token['access_token'],7100);
        }else{
            $this->access_token = $data;
        }
        return $this->access_token  = $access_token['access_token'];
     }
 }
