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
        $postObj = $decryptionResult['object'];
        //消息类型分离InfoType
        self::$InfoType = trim($postObj->InfoType);
        $result['InfoType'] = self::$InfoType;
        $result['msg'] = $decryptionResult['msg'];

        switch (self::$InfoType)
        {
            case "component_verify_ticket":
                $ComponentVerifyTicket = trim($postObj->ComponentVerifyTicket);
                self::$Redis->set(self::$Config['appid'].'_ComponentVerifyTicket',$ComponentVerifyTicket);
                self::component_access_token();
                $result['result'] = self::component_access_token();
                break;

            default:

                break;
        }

        return $result;

    }

    /**
     * 获取 component_access_token
     * @return mixed
     * @throws \Exception
     */
    public static function component_access_token()
    {
        $result = self::$Redis->get(self::$Config['appid'].'_component_access_token');
        $ComponentVerifyTicket = self::$Redis->get(self::$Config['appid'].'_ComponentVerifyTicket');

        if(empty($result)){
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $postData = [
                'component_appid'=>self::$Config['appid'],
                'component_appsecret'=>self::$Config['appsecret'],
                'component_verify_ticket'=>$ComponentVerifyTicket,
            ];
            $result = Func::http_request($url,json_encode($postData));
            $resultJson = json_decode($result,true);

            if(isset($resultJson['errcode'])){
                throw new \Exception($result);
            }

            self::$Redis->set(self::$Config['appid'].'_component_access_token',$result,7100);
        }
        return json_decode($result,true);
    }

    /**
     * 获取授权连接
     * @param null   $id
     * @param string $redirect_uri
     * @return string
     * @throws \Exception
     */
    public static function getAccreditUrl($id=null,$redirect_uri='')
    {
        if(empty($redirect_uri)){
            throw new \Exception('redirect_uri是必须的');
        }
        return $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.self::$Config['appid'].'&pre_auth_code='.self::pre_auth_code($id)['pre_auth_code'].'&redirect_uri='.$redirect_uri;
    }
    /**
     *  pre_auth_code预授权码用于公众号授权时的第三方平台方安全验证。
     * @param $id
     * @param bool $cache 判断是否缓存
     * @return mixed
     * @throws \Exception
     */
    public static function pre_auth_code($id,$cache=false)
    {
        /**
         * 判断是否缓存
         */
        if($cache){
            $pre_auth_code = self::$Redis->get(self::$Config['appid'].'_'.$id.'_pre_auth_code');
            if(!empty($pre_auth_code)){
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.self::component_access_token()['component_access_token'];
                $postData = [
                    'component_appid'=>self::$Config['appid'],
                ];
                $pre_auth_code = Func::http_request($url,json_encode($postData));
                $pre_auth_code_json = json_decode($pre_auth_code,true);
                if(isset($resultJson['errcode'])){
                    throw new \Exception($pre_auth_code_json);
                }
                self::$Redis->set(self::$Config['appid'].'_'.$id.'_pre_auth_code',$pre_auth_code,1740);
            }
        }else{

            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.self::component_access_token()['component_access_token'];
            $postData = [
                'component_appid'=>self::$Config['appid'],
            ];
            $pre_auth_code = Func::http_request($url,json_encode($postData));
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
        $result = Func::http_request($url,json_encode($postData));
        $authorization = json_decode($result,true);
        if(isset($authorization['errcode'])){
            throw new \Exception($result);
        }
        return $authorization;

    }

    /**
     * 刷新authorizer_access_token
     * @param $authorizerAppid
     * @param $authorizerRefreshToken
     * @return mixed
     * @throws \Exception
     */
    public static function authorizer_access_token($authorizerAppid,$authorizerRefreshToken)
    {

        $result = self::$Redis->get($authorizerAppid.'_authorizer_access_token');

        if(empty($result)){
            $postData = [
                "component_appid"=>self::$Config['appid'],
                "authorizer_appid"=>$authorizerAppid,
                "authorizer_refresh_token"=>$authorizerRefreshToken,
            ];
            $url =  'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.self::component_access_token()['component_access_token'];
            $authorization = Func::http_request($url,json_encode($postData));
            $result = json_decode($authorization,true);
            if(isset($result['errcode'])){
                throw new \Exception(json_encode($authorization));
            }
            self::$Redis->set($authorizerAppid.'_authorizer_access_token',$authorization,7100);
        }
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
            $authorization = Func::http_request($url.$param,$decryptionResult['msg']);

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
        //file_put_contents('get',json_encode($get));
        //file_put_contents('input',$input);
        $WXBizMsgCrypt       = new WXBizMsgCrypt(self::$Config['token'], self::$Config['encodingAesKey'],self::$Config['appid']);
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
        $result = Func::http_request($url,json_encode($postData));
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
        $result = self::$Redis->get($authorizerAppid.'_jsapi_ticket');
        if(!empty($result)){
            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.self::authorizer_access_token($authorizerAppid,$authorizerRefreshToken)['authorizer_access_token'].'&type=jsapi';
            $resultJson = Func::http_request($url);
            //{"errcode":0,"errmsg":"ok","ticket":"gdzxN2g622vVfv3lEXnBsieQGLWrJc2oC4JUVAGX4zivz5xUvsuDr8v_gHyiZYwhqPiER19QeAIuVQGqM7oL2w","expires_in":7200}
            $resultArr = json_decode($resultJson,true);
            if($resultArr['errmsg'] != 'ok'){
                throw new \Exception($resultJson);
            }
            self::$Redis->set($authorizerAppid.'_jsapi_ticket',$resultJson,7100);
            return $resultArr;
        }
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




}