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
class AccessToken{

     protected $config = '';//配置信息
     protected $site_file = '../runtime/cache/access_token.json';
     protected $redis = '';//redis
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
      * [redis redis缓存]
      * @Effect
      * @return [type] [description]
      */
     protected function redis_cache()
     {
         $RedisModel = new RedisModel();
         $this->redis = $RedisModel->redis;
//        $redis = new \Redis();
//        $redis->connect($this->config['host'], $this->config['port'],1);
//        if(!empty($this->config['password'])){
//            $redis->auth($this->config['password']);//登录验证密码，返回【true | false】
//        }
//
//        $redis->select($this->config['select']);
//        $this->redis = $redis;
         //获取判断
        $this->access_token = $this->redis->get($this->config['type']);

//        var_dump($this->redis->ttl($this->config['type']));

         if(!$this->access_token){
             //获取
             if(!$this->get_access_token()){
                 return false;
             }
             //存储
             $this->redis->set($this->config['type'],$this->access_token['access_token']);

             $this->redis->expire($this->config['type'],$this->access_token['expires_in']);
             return $this->access_token['access_token'];
         }
        return $this->access_token;

     }

     /**
      * [ file缓存]
      * @Effect
      * @return [type] [description]
      */
     protected function file_cache()
     {
        //读取文件
        $res = file_get_contents($this->site_file);        
        $this->access_token = json_decode($res, true);
        //如果不存在  比如从redis 切换到file
        if(!isset($this->access_token['expires_time'])){
            if(!$this->get_access_token()){
                return false;
            }
            // expires_time 创建时间
            // expires_in 有效期时间
            file_put_contents(
                $this->site_file, '{"access_token": "'.$this->access_token['access_token'].'", "expires_time": '.time().',"expires_in": '.$this->access_token['expires_in'].'}'
                );

        }else if(time() > ($this->access_token['expires_time'] + $this->access_token['expires_in'])){
            if(!$this->get_access_token()){
                return false;
            }
            // expires_time 创建时间
            // expires_in 有效期时间
            file_put_contents(
                $this->site_file, '{"access_token": "'.$this->access_token['access_token'].'", "expires_time": '.time().',"expires_in": '.$this->access_token['expires_in'].'}'
                );
        }

        return $this->access_token['access_token'];
     }

     /**
      * [access_token 获取]
      * @Effect
      * @return [type] [description]
      * @throws \Exception
      */
     public function access_token($authorizerAppid = null,$authorizerRefreshToken = null){

        /**
        * 判断是否是第三方开发模式
         * hird 第三方  tradition 传统模式
        */
        if($this->config['pattern'] === 'third')
        {
            Open::init($this->config,$this->redis);
            $this->access_token = Open::authorizer_access_token($authorizerAppid,$authorizerRefreshToken)['authorizer_access_token'];
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
     * @return [type]       [description]
     */
    protected function tradition_access_token()
    {

        $this->redis->get($this->config['prefix'].'access_token'.$this->config['appid']);

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->config['appid']."&secret=".$this->config['appsecret'];
        $res = Func::http_request($url);
        $access_token = json_decode($res, true);
        if(isset($access_token['errcode'])){
            throw new Exception($access_token['errmsg']);
        }
        return $this->access_token  =$access_token['access_token'];
     }
 }
