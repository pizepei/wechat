<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/15
 * Time: 17:36
 * @title 微信二维码类
 */


namespace pizepei\wechat\basics;

use pizepei\helper\Helper;
use pizepei\model\redis\Redis;
use pizepei\wechat\service\Config;
use GuzzleHttp\Client;

class QrCode
{
    /**
     * 公众号appid
     * @var null
     */
    protected $authorizerAppid = null;
    /**
     * @var Config|null
     */
    protected $config = null;
    /**
     * QrCode constructor.
     * @param $authorizerAppid
     */
    public function __construct($authorizerAppid)
    {
        # 加载配置
        $this->authorizerAppid = $authorizerAppid;
        $this->config = new Config(Redis::init());

    }
    private  $expire_seconds = '';//过期时间
    private  $scene_id = '';//参数
    private $action_name = '';
    private $access_token = '';
    private $ticket ='';

    /**
     * Ticket 获取
     * @param $scene_id 场景id 建议uuid
     * @param int $type 二维码类型
     * @param int $expire_seconds  有效期 0 为永久 单位s
     * @param $http_agent
     * @return bool|mixed
     * @throws \Exception
     */
    public  function get_ticket($scene_id='',$type=0,$expire_seconds = 60){

        if ($scene_id == ''){
            # 没有设置  自动生成uuid
            $scene_id = Helper::init()->getUuid();
        }
        $authorizer_access_token = $this->config->access_token($this->authorizerAppid)['authorizer_access_token'];
        # 判断永久还是临时   默认临时
        if($expire_seconds !== 0 ){
            # 临时
            $qrcode = '{"expire_seconds": '.$expire_seconds.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
        }else{
            # 永久
            $qrcode = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$authorizer_access_token;
        $res  = Helper::init()->httpRequest($url,$qrcode);
        if ($res['RequestInfo']['http_code'] !== 200){
            throw new \Exception('初始化配置失败：请求配置中心失败');
        }
        if (Helper::init()->is_empty($res,'body')){
            throw new \Exception('请求失败');
        }
        $body =  Helper::init()->json_decode($res['body']);
        if (!isset($body['ticket']) || isset($body['errcode'])){
            throw new \Exception($body['errmsg']);
        }
        /**
         * 处理数据
         */
        $body['src'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$body['ticket'];
        $body['scene_id'] = $scene_id;
        $body['type'] = $type;
        return $body;

    }


}