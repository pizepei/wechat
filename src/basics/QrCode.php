<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/15
 * Time: 17:36
 * @title 微信二维码类
 */


namespace pizepei\wechat\basics;

use pizepei\encryption\aes\Prpcrypt;
use pizepei\helper\Helper;
use pizepei\model\redis\Redis;
use pizepei\wechat\model\OpenWechatQrCodeModel;
use pizepei\wechat\model\OpenWechatQrCodeVerifiModel;
use pizepei\wechat\service\Config;
use GuzzleHttp\Client;
use pizepei\encryption\SHA1;

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
    public  function get_ticket($scene_id='',int $type=0,$expire_seconds = 60,int $terrace=1,$parameter=[]){

        if (!isset(BasicsConst::QrcodeType[$type])){
            throw new \Exception('Type error');
        }
        if ($scene_id == ''){
            # 没有设置  自动生成uuid
            $scene_id = Helper::init()->getUuid();
        }
        $scene_id_db = $scene_id;
//     $action_name   二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
        if(is_int($scene_id) && $scene_id <20000000){
            $scene = 'scene_id';
            $expire_seconds == 0?$action_name = 'QR_LIMIT_SCENE':$action_name = 'QR_SCENE';
        }else{
            $expire_seconds == 0?$action_name = 'QR_LIMIT_STR_SCENE':$action_name = 'QR_STR_SCENE';
            $scene = 'scene_str';
            $scene_id = '"'.$scene_id.'"';
        }
        $authorizer_access_token = $this->config->access_token($this->authorizerAppid)['authorizer_access_token'];
        # 判断永久还是临时   默认临时
        if($expire_seconds){
            # 临时 的
            $qrcode = '{"expire_seconds": '.$expire_seconds.', "action_name": "'.$action_name.'", "action_info": {"scene": {"'.$scene.'": '.$scene_id.'}}}';
        }else{
            # 永久
            $qrcode = '{"action_name": "'.$action_name.'", "action_info": {"scene": {"'.$scene.'": '.$scene_id.'}}}';
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
        $body['scene_id'] = $scene_id_db;
        $body['type'] = $type;
        $Open = OpenWechatQrCodeModel::table($this->authorizerAppid)->add([
            'authorizer_appid'=>$this->authorizerAppid,
            'expire_seconds'=>$expire_seconds,
            'scene_id'=>$scene_id_db,
            'ticket'=>$body['ticket'],
            'content'=>$qrcode,
            'terrace'=>$terrace,
            'terrace_name'=>$parameter['terrace_name']??[],
            'type'=>BasicsConst::QrcodeType[$type],
            'extend'=>$parameter,
        ]);
        if (Helper::init()->is_empty($Open)){
            throw new \Exception('创建二维码记录异常');
        }
        $body['qr_id'] = key($Open);
        $body['type'] = BasicsConst::QrcodeType[$type];
        return $body;

    }
    /**
     * @param string $number
     * @param int $type 类型
     * @param int $terrace 有效期
     * @param int $frequency 频率
     * @return bool|mixed
     * @throws \Exception
     */
    public function numberVerificationCode(string $number,int $type,int $terrace,int $frequency)
    {
        # 读取记录判断是否已经有发送
        $OpenWechatQrCodeVerifi = OpenWechatQrCodeVerifiModel::table();
        $data = $OpenWechatQrCodeVerifi->where([
            'authorizer_appid'=>$this->authorizerAppid,
            'number'=>$number,
            'creation_time'=>['GT',date('Y-m-d H:i:s',time()-$frequency)],
        ])->fetch();
        if (!Helper::init()->is_empty($data)){
            throw new \Exception('频率过高');
        }
        # 获取二维码
        $data = $this->get_ticket('',$type,$terrace);

        if (Helper::init()->is_empty($data['ticket'])){
        }
        $data['authorizer_appid'] =$this->authorizerAppid;
        $data['number'] =$number;
        $data['qr_id'] =$data['qr_id'];
        $data['frequency'] =$frequency;
        $data['reply_content'] = '验证成功';
        $OpenWechatQrCodeVerifi->add($data);
        return $data;
    }


    public function responseQr($config,$data)
    {
        # ip白名单
        if (!empty($config['ip_white_list']))
        {
            $ip = Helper::init()->get_ip();
            if (!in_array($ip,$config['ip_white_list']))  throw new \Exception('非法请求：'.$ip);
        }
        $Prpcrypt = new Prpcrypt($config['encoding_aes_key']);
        $SHA1 = new SHA1();
        if (!$SHA1->verifySignature($config['token'],$data)){
            throw new \Exception('签名错误');
        }
        $data = $Prpcrypt->decrypt($data['encrypt_msg']);
        if (!isset($data[1])) throw new \Exception('数据错误');

        $param = Helper::init()->json_decode($data[1]);

        # 获取二维码
        $data = $this->get_ticket('',0,$param['terrace']??60);

        if (Helper::init()->is_empty($data['ticket'])){
            throw new \Exception('请求二维码错误');
        }

        $data['authorizer_appid'] =$this->authorizerAppid;
        $data['number'] =$number;
        $data['qr_id'] =$data['qr_id'];
        $data['frequency'] =$frequency;
        $data['reply_content'] = '验证成功';
        $OpenWechatQrCodeVerifi = OpenWechatQrCodeVerifiModel::table();
        $OpenWechatQrCodeVerifi->add($data);
        return $data;



        $this->numberVerificationCode(13266579753,1,60,2);
        return $param;

    }
}