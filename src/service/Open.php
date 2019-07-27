<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/2/21
 * Time: 17:36
 * @title 微信公众号第三方开放平台
 * @utl https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&id=open1419318587&token=&lang=zh_CN
 */

namespace pizepei\wechat\service;

use pizepei\helper\Helper;
use pizepei\model\redis\Redis;
use pizepei\wechat\model\OpenAuthorizerUserInfoModel;
use pizepei\wechat\basics\Func;
use pizepei\wechat\basics\Prpcrypt;
use pizepei\wechat\basics\SHA1;
use pizepei\wechat\basics\WXBizMsgCrypt;

class Open
{
    /**
     * 配置
     * @var array
     */
    public static $Config = [
        ];
    /**
     * @var string
     */
    protected static $cache_prefix = 'wechat:open:';

    /**
     * redis缓存对象
     * @var null
     */
    public static $Redis = null;
    /**
     * 解密后的对象
     * @var null
     */
    public static $postObj =null;
    /**
     * 事件类型
     * @var null
     */
    public static $InfoType = null;
    /**
     * @param array  $Config
     * @param object $Redis
     */
    public static function init(array $Config=array(),object $Redis=null)
    {

        if(empty(self::$Config))
        {
            if(!is_array($Config) || empty($Config)){
                throw new \Exception('Config 必须的');
            }
            self::$Config = $Config;
        }
        if(empty(self::$Redis))
        {
            if(empty($Redis)){
                throw new \Exception('Redis 必须的');
            }
            self::$Redis = $Redis;
        }
    }
    /**
     * @Author 皮泽培
     * @Created 2019/7/13 11:59
     * @title  设置授权信息
     * @explain 设置授权信息 包括 修改授权、先增授权、权限授权
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
     */
    public static function setAuthorized(&$result,$InfoType)
    {
        $OpenAuthorizer = OpenAuthorizerUserInfoModel::table();
        /**
         * 判断是否已经有授权数据防止写入重复数据重新错误
         */
        $OpenAuthorizerData = $OpenAuthorizer->where(
            [
                'authorizer_appid'=>$result['postObj']['AuthorizerAppid'],
            ]
        )->fetch();
        /**
         * 通过授权代码获取授权公众号的基本授权信息(所有)
         * authorized 增加授权   updateauthorized 更新授权
         */
        if ($InfoType == 'authorized' ||$InfoType == 'updateauthorized' ){
            $authorizerAccessInfo = self::auth_code($result['postObj']['AuthorizationCode']);
            $result['result'] = $authorizerAccessInfo;
            if (!empty($OpenAuthorizerData) && isset($authorizerAccessInfo['authorization_info'])){
                $authorizerAccessInfo['id'] = $OpenAuthorizerData['id'];
                $authorizerAccessInfo['status'] = 2;
            }else if (!isset($authorizerAccessInfo['authorizer_appid'])){
                throw new \Exception('获取 authorization_info 失败');
            }
        }else if ($InfoType == 'unauthorized') {//取消授权
            $authorizerAccessInfo['id'] = $OpenAuthorizerData['id'];
            $authorizerAccessInfo['status'] = 3;
        }
        $authorizerAccessInfo['component_appid'] = self::$Config['appid'];
        return $OpenAuthorizer->insert($authorizerAccessInfo);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/13 11:53
     * @title  更新方法component_verify_ticket
     * @explain 更新verify_ticket缓存
     */
    public static function  component_verify_ticket($result)
    {
        $ComponentVerifyTicket = trim($result['postObj']['ComponentVerifyTicket']);
        self::$Redis->set(self::$Config['cache_prefix'].self::$Config['appid'].'_ComponentVerifyTicket',$ComponentVerifyTicket);
        return self::component_access_token();
    }
    /**
     * 第三方授权
     * @param $get
     * @param $input
     * @return array
     * @throws \Exception
     */
    public static function accredit($get,$input)
    {
        if(empty($input)){
            throw new \Exception('xml为空');
        }
        $decryptionResult = self::decryption($get,$input);
        $result['postObj'] = json_decode(json_encode($decryptionResult['object']), true);

        //消息类型分离InfoType
        $result['InfoType'] = $result['postObj']['InfoType'];
        $result['msg'] = $decryptionResult['msg'];
        switch ($result['postObj']['InfoType'])
        {
            case "component_verify_ticket":
                /**
                 * component_verify_ticket更新
                 */
                $result['result'] = self::component_verify_ticket($result);
                break;
            case "unauthorized"://取消授权
                self::setAuthorized($result,$result['postObj']['InfoType']);
                break;

            case "authorized"://授权
                /**
                 * 通过授权代码获取授权公众号的基本授权信息(所有)
                 */
                self::setAuthorized($result,$result['postObj']['InfoType']);
                break;
            case "updateauthorized"://修改权限
                /**
                 * 通过授权代码获取授权公众号的基本授权信息(所有)
                 */
                self::setAuthorized($result,$result['postObj']['InfoType']);
                break;
            default:
                break;
        }
        return $result;

    }

    /**
     * @Author pizepei
     * @Created 2019/3/2 15:20
     * @param $auth_code
     * @title  通过auth_code获取授权事件信息
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public static function auth_code($auth_code){

        /**
         * 通过授权代码获取授权公众号的基本授权信息
         */
        $authorizerAccessInfo = self::authorizerAccessInfo(['auth_code'=>$auth_code]);
        $authorizerAccessInfoData = $authorizerAccessInfo['authorization_info'];
        /**
         * 通过授权公众号的appid获取公众号的详细信息
         */
        $authorizerInfo = self::authorizerInfo($authorizerAccessInfoData['authorizer_appid']);
        /**
         * 合并数据
         */
        $authorizerAccessInfoData['PreAuthCode'] = $auth_code;

        $authorizerAccessInfoData['nick_name'] = $authorizerInfo['authorizer_info']['nick_name'];
        $authorizerAccessInfoData['head_img'] = $authorizerInfo['authorizer_info']['head_img'];
        $authorizerAccessInfoData['service_type_info'] = $authorizerInfo['authorizer_info']['service_type_info'];
        $authorizerAccessInfoData['verify_type_info'] = $authorizerInfo['authorizer_info']['verify_type_info'];
        $authorizerAccessInfoData['user_name'] = $authorizerInfo['authorizer_info']['user_name'];
        $authorizerAccessInfoData['alias'] = $authorizerInfo['authorizer_info']['alias'];
        $authorizerAccessInfoData['qrcode_url'] = $authorizerInfo['authorizer_info']['qrcode_url'];
        $authorizerAccessInfoData['business_info'] = $authorizerInfo['authorizer_info']['business_info'];
        $authorizerAccessInfoData['idc'] = $authorizerInfo['authorizer_info']['idc'];
        $authorizerAccessInfoData['principal_name'] = $authorizerInfo['authorizer_info']['principal_name'];
        $authorizerAccessInfoData['signature'] = $authorizerInfo['authorizer_info']['signature'];

        $authorizerAccessInfoData['authorizer_appid'] = $authorizerInfo['authorization_info']['authorizer_appid'];
        $authorizerAccessInfoData['authorizer_refresh_token'] = $authorizerInfo['authorization_info']['authorizer_refresh_token'];
        $authorizerAccessInfoData['func_info'] = $authorizerInfo['authorization_info']['func_info'];
        return $authorizerAccessInfoData;

    }
    /**
     * 获取 component_access_token
     * @return mixed
     * @throws \Exception
     */
    public static function component_access_token()
    {

        $dd = Helper::init()->syncLock(Redis::init(),['open','component_access_token',self::$Config['appid']],true,'access_token');#设置Lock
        $result = self::$Redis->get(self::$Config['cache_prefix'].self::$Config['appid'].'_component_access_token');
        $ComponentVerifyTicket = self::$Redis->get(self::$Config['cache_prefix'].self::$Config['appid'].'_ComponentVerifyTicket');
        if(empty($result)){
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $postData = [
                'component_appid'=>self::$Config['appid'],
                'component_appsecret'=>self::$Config['appsecret'],
                'component_verify_ticket'=>$ComponentVerifyTicket,
            ];
            $result =  Helper::init()->httpRequest($url,json_encode($postData))['body'];
            $resultJson = json_decode($result,true);
            if(isset($resultJson['errcode'])){
                $dd = Helper::init()->syncLock(Redis::init(),['open','component_access_token',self::$Config['appid']],false,'access_token');#解除Lock
                throw new \Exception($resultJson['errmsg'].'['.$resultJson['errcode'].']');
            }
            self::$Redis->set(self::$Config['cache_prefix'].self::$Config['appid'].'_component_access_token',$result,7100);
        }
        Helper::init()->syncLock(Redis::init(),['open','component_access_token',self::$Config['appid']],false);#解除Lock
        return json_decode($result,true);
    }
    /**
     * 获取授权连接
     * @param null   $id
     * @param string $redirect_uri
     * @return array
     * @throws \Exception
     */
    public static function getAccreditUrl($id=null,$redirect_uri='')
    {
        if(empty($redirect_uri)){
            throw new \Exception('redirect_uri是必须的');
        }
        $pre_auth_code = self::pre_auth_code($id)['pre_auth_code'];
        $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.self::$Config['appid'].'&pre_auth_code='.$pre_auth_code.'&redirect_uri='.$redirect_uri;

        return ['pre_auth_code'=>$pre_auth_code,'url'=>$url];
    }
    /**
     *  pre_auth_code预授权码用于公众号授权时的第三方平台方安全验证。
     * @param $id
     * @param bool $cache 判断是否缓存
     * @return mixed
     * @throws \Exception
     */
    public static function pre_auth_code($id='',$cache=false)
    {
        /**
         * 判断是否缓存
         */
        if($cache){
            $pre_auth_code = self::$Redis->get(self::$Config['cache_prefix'].self::$Config['appid'].'_'.$id.'_pre_auth_code');
            if(!empty($pre_auth_code)){
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.self::component_access_token()['component_access_token'];
                $postData = [
                    'component_appid'=>self::$Config['appid'],
                ];
                $pre_auth_code = Helper::init()->httpRequest($url,json_encode($postData))['body'];
                $pre_auth_code_json = json_decode($pre_auth_code,true);
                if(isset($resultJson['errcode'])){
                    throw new \Exception($pre_auth_code_json);
                }
                self::$Redis->set(self::$Config['cache_prefix'].self::$Config['appid'].'_'.$id.'_pre_auth_code',$pre_auth_code,1740);
            }
        }else{

            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.self::component_access_token()['component_access_token'];
            $postData = [
                'component_appid'=>self::$Config['appid'],
            ];

            $pre_auth_code = Helper::init()->httpRequest($url,json_encode($postData))['body'];
            $pre_auth_code_json = json_decode($pre_auth_code,true);
            if(isset($resultJson['errcode'])){
                throw new \Exception($pre_auth_code_json);
            }
        }

        return json_decode($pre_auth_code,true);
    }

    /**
     * 获取授权信息
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public static function authorizerAccessInfo($request)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.self::component_access_token()['component_access_token'];
        $postData = [
            "component_appid"=>self::$Config['appid'],
            "authorization_code"=>$request['auth_code'],
        ];
        $result = Helper::init()->httpRequest($url,json_encode($postData))['body'];
        $authorization = json_decode($result,true);
        if(isset($authorization['errcode'])){
            throw new \Exception($result);
        }
        return $authorization;

    }

    /**
     * @Author pizepei
     * @Created 2019/3/3 13:25
     *
     * @param $authorizerAppid
     * @param $authorizerRefreshToken
     * @param $restart
     * @return mixed
     * @throws \Exception
     *
     * @title  获取授权方authorizer_access_token
     * @explain $restartw 为true时强制获取
     *
     */
    public static function authorizer_access_token($authorizerAppid,$authorizerRefreshToken,$restart=false)
    {
        $dd = Helper::init()->syncLock(Redis::init(),['open','authorizer_access_token',$authorizerAppid],true,'access_token');#Lock
        $result = self::$Redis->get(self::$Config['cache_prefix'].':'.$authorizerAppid.':authorizer_access_token');
        if(empty($result) || $restart){

            $postData = [
                "component_appid"=>self::$Config['appid'],
                "authorizer_appid"=>$authorizerAppid,
                "authorizer_refresh_token"=>$authorizerRefreshToken,
            ];
            $url =  'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.self::component_access_token()['component_access_token'];

            $authorization = Helper::init()->httpRequest($url,json_encode($postData))['body'];
            $result = json_decode($authorization,true);
            if(isset($result['errcode'])){
                Helper::init()->syncLock(Redis::init(),['open','authorizer_access_token',$authorizerAppid],false,'access_token');#解除Lock
                throw new \Exception(json_encode($authorization));
            }
            $result['expires_time'] = time()+$result['expires_in']-1;
            $result['expires_date'] = date('Y-m-d H:i:s',time()+$result['expires_in']-1);
            /**
             * 修改更新
             */
            OpenAuthorizerUserInfoModel::table()
                ->where(['component_appid'=>self::$Config['appid'],'authorizer_appid'=>$authorizerAppid])
                ->update([
                    'authorizer_refresh_token'=>$result['authorizer_refresh_token'],
                    'authorizer_access_token'=>$result['authorizer_refresh_token'],
                ]);
            self::$Redis->set(self::$Config['cache_prefix'].':'.$authorizerAppid.':authorizer_access_token',json_encode($result),7100);
            Helper::init()->syncLock(Redis::init(),['open','authorizer_access_token',$authorizerAppid],false);#解除Lock
            return $result;
        }
        $dd = Helper::init()->syncLock(Redis::init(),['open','authorizer_access_token',$authorizerAppid],false);#解除Lock
        return json_decode($result,true);

    }

    /**
     * 处理信息
     * @param        $get
     * @param        $input
     * @param        $appid
     * @param        $url
     * @param string $encodingAesKey
     * @param string $token
     * @return array|mixed
     * @throws \Exception
     */
    public static function message($get,$input,$appid,$url,$token='',$encodingAesKey='')
    {


        if(empty($input) || empty($get)){
            throw new \Exception('参数不能为空');
        }
        /**
         * 解密
         */
        $decryptionResult = self::decryption($get,$input);

        /**
         *判断是否需要重新加密
         */
        if(!empty($appid) && !empty($encodingAesKey) &&!empty($token)){
            /**
             * 重新加密
             */
            $decryptionResult['msg'] = self::encryption($decryptionResult['msg'],$token,$encodingAesKey,$appid,$get['timestamp'],$get['nonce']);

        }
        /**
         * 判断是否需要转发
         */
        if(!empty($appid) && !empty($url)){
            /**
             * 重新设置签名
             */

            /**
             * 获取密文
             */
            $xmltext = simplexml_load_string($decryptionResult['msg'], 'SimpleXMLElement', LIBXML_NOCDATA);
            $Encrypt = $xmltext->Encrypt;
            $ToUserName = $xmltext->ToUserName;
            $sha1 = new SHA1;
            $array = $sha1->getSHA1($token, $get['timestamp'], $get['nonce'], $Encrypt);
            $get['msg_signature'] = $array[1];
            $param = http_build_query($get);
            /**
             * 转发
             */
            $authorization = Helper::init()->httpRequest($url.$param,$decryptionResult['msg'])['body'];
            if(!empty($appid) && !empty($encodingAesKey) &&!empty($token)){
                $authorization = self::simpleDecryption( $encodingAesKey,$authorization);
            }
            if(empty($authorization)){
                return 'success';
            }
            return $authorization;
        }
        return $decryptionResult;

    }

    /**
     * 解密
     * @param      $get
     * @param      $input
     * @return array
     */
    public static function decryption($get,$input)
    {

        $WXBizMsgCrypt       = new WXBizMsgCrypt(self::$Config['token'], self::$Config['EncodingAESKey'],self::$Config['appid']);
        $WXBizMsgCrypt->decryptMsg($get['msg_signature'], $get['timestamp'], $get['nonce'], $input, $msg);

        libxml_disable_entity_loader(true);
        $object = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        return ['object'=>$object??null,'msg'=>$msg];
    }

    /**
     * 简单的解密
     * @param $encodingAesKey
     * @param $authorization
     * @return mixed
     */
    public static function simpleDecryption( $encodingAesKey,$authorization)
    {
        $pc = new Prpcrypt($encodingAesKey);
        $xmltext = simplexml_load_string($authorization, 'SimpleXMLElement', LIBXML_NOCDATA);
        $Encrypt = $xmltext->Encrypt;
        return $authorization = $pc->decrypt($Encrypt)[1];
    }

    /**
     * 加密
     * @param $replyMsg
     * @param $token
     * @param $encodingAesKey
     * @param $appid
     * @param $timeStamp
     * @param $nonce
     * @return mixed
     */
    public static function encryption($replyMsg,$token,$encodingAesKey,$appid,$timeStamp,$nonce)
    {
        $WXBizMsgCrypt       = new WXBizMsgCrypt($token,$encodingAesKey,$appid);
        $WXBizMsgCrypt->encryptMsg($replyMsg, $timeStamp, $nonce, $replyMsg);

        return $replyMsg;
    }

    /**
     * https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&component_appid=component_appid#wechat_redirect
     */
    public static function OAuth20($appid,$redirect_uri,$response_type,$scope,$state)
    {
        return $utl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect_uri.'&response_type='.$response_type.'&scope='.$scope.'&state='.$state.'&component_appid='.self::$Config['appid'].'#wechat_redirect';
    }

    /**
     * 获取授权方公众号的详细信息
     * @param $authorizer_appid
     * @return mixed
     * @throws \Exception
     */
    public static function authorizerInfo($authorizer_appid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.self::component_access_token()['component_access_token'];
        $postData = [
            "component_appid"=>self::$Config['appid'],
            "authorizer_appid"=>$authorizer_appid,
        ];

        $result = Helper::init()->httpRequest($url,json_encode($postData))['body'];
        $authorization = json_decode($result,true);

        if(isset($authorization['errcode'])){
            throw new \Exception($result);
        }
        return $authorization;
    }

    /**
     *  https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi
     *
     * @param $authorizerAppid
     * @param $authorizerRefreshToken
     * @return mixed
     * @throws \Exception
     */
    public static function jsapi_ticket($authorizerAppid,$authorizerRefreshToken)
    {
        Helper::init()->syncLock(Redis::init(),['open','jsapi_ticket',$authorizerAppid]);#设置Lock
        $result = self::$Redis->get(self::$Config['cache_prefix'].$authorizerAppid.'_jsapi_ticket');
        if(!empty($result)){
            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.self::authorizer_access_token($authorizerAppid,$authorizerRefreshToken)['authorizer_access_token'].'&type=jsapi';
            $resultJson = Helper::init()->httpRequest($url)['body'];
            //{"errcode":0,"errmsg":"ok","ticket":"gdzxN2g622vVfv3lEXnBsieQGLWrJc2oC4JUVAGX4zivz5xUvsuDr8v_gHyiZYwhqPiER19QeAIuVQGqM7oL2w","expires_in":7200}
            $resultArr = json_decode($resultJson,true);
            if($resultArr['errmsg'] != 'ok'){
                throw new \Exception($resultJson);
            }
            self::$Redis->set(self::$Config['cache_prefix'].$authorizerAppid.'_jsapi_ticket',$resultJson,7100);
            Helper::init()->syncLock(Redis::init(),['open','jsapi_ticket',$authorizerAppid],false);#解除Lock
            return $resultArr;
        }
        Helper::init()->syncLock(Redis::init(),['open','jsapi_ticket',$authorizerAppid],false);#解除Lock

        return json_decode($result,true);
    }

    /**
     * @param $url
     * @param $authorizerAppid
     * @param $authorizerRefreshToken
     */
    public static function jsapi_signature($url,$authorizerAppid,$authorizerRefreshToken)
    {
        $noncestr = self::randomStr(16);
        $jsapi_ticket = self::jsapi_ticket($authorizerAppid,$authorizerRefreshToken)['ticket'];
        $timestamp = time();
        $param = [
          'noncestr'=>  $noncestr,
          'jsapi_ticket' =>$jsapi_ticket,
          'timestamp' =>$timestamp,
          'url'=>$url,
        ];
        /**
         * 排序
         */
        ksort($param);
        /**
         * 拼接
         */
        $str = '';
        foreach($param as $key => $value){
            $str .= $key.'='.$value.'&';
        }
        $str       = trim($str, '&');
        //$paramStr = http_build_query($param);
        return [
            'appid' =>$authorizerAppid,
            'noncestr'=>$noncestr,
            'timestamp'=>$timestamp,
            'url'=>$url,
            'signature'=>sha1($str),
        ];
    }

    /**
     * 生成给定长度的随机字符串
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function randomStr(int $length = 16)
    {
        $string = '';

        while(($len = strlen($string)) < $length){
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }


    /**
     * 将xml转为array
     * @param  string 	$xml xml字符串或者xml文件名
     * @param  bool 	$isfile 传入的是否是xml文件名
     * @return array    转换得到的数组
     */
    public static function xmlToArray($xml,$isfile=false){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        if($isfile){
            if(!file_exists($xml)) return false;
            $xmlstr = file_get_contents($xml);
        }else{
            $xmlstr = $xml;
        }
        $result= json_decode(json_encode(simplexml_load_string($xmlstr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

}