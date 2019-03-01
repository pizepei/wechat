<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/2/22
 * Time: 13:51
 */

namespace  pizepei\wechat\basics;


class Func
{
    /**
     * 定义url命名规则
     * 前置
     *  KF 客服
     */
    const URL_ARR= [
        'KF_LIST'=>['https://api.weixin.qq.com/cgi-bin/customservice/getkflist?access_token={%ACCESS_TOKEN%}','获取客服列表'],
        'KF_ADD'=>['https://api.weixin.qq.com/customservice/kfaccount/add?access_token={%ACCESS_TOKEN%}','添加客服'],
        'KF_CREATE'=>['https://api.weixin.qq.com/customservice/kfsession/create?access_token={%ACCESS_TOKEN%}','创建客服会话'],
        'KF_SEND_V1'=>['https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={%ACCESS_TOKEN%}','客服向客户发送信息'],
        'MENU_ADD'=>['https://api.weixin.qq.com/cgi-bin/menu/create?access_token={%ACCESS_TOKEN%}','创建自定义菜单'],
        'STORE_CREATE'=>['http://api.weixin.qq.com/cgi-bin/poi/addpoi?access_token={%ACCESS_TOKEN%}','创建门店'],
        'STORE_CREATE_UPLOAD'=>['https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token={%ACCESS_TOKEN%}','创建门店上传图片'],
        'STORE_POI_LIST'=>['https://api.weixin.qq.com/cgi-bin/poi/getpoilist?access_token={%ACCESS_TOKEN%}','查询门店列表'],
        'STORE_CATEGORY'=>['http://api.weixin.qq.com/cgi-bin/poi/getwxcategory?access_token={%ACCESS_TOKEN%}','获取微信门店类目'],
        'STORE_DEL_POI'=>['https://api.weixin.qq.com/cgi-bin/poi/delpoi?access_token={%ACCESS_TOKEN%}','删除门店'],
        'STORE_UPDATE_POI'=>['https://api.weixin.qq.com/cgi-bin/poi/updatepoi?access_token={%ACCESS_TOKEN%}','更新门店类目'],
        'STORE_POI'=>['http://api.weixin.qq.com/cgi-bin/poi/getpoi?access_token={%ACCESS_TOKEN%}','查询门店信息'],
        'GET_MEDIA_ID'=>['https://api.weixin.qq.com/cgi-bin/media/get?access_token={%ACCESS_TOKEN%}&media_id={%MEDIA_ID%}','获取媒体'],
        'GET_ALL_PRIVATE_TEMPLATE' =>['https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={%ACCESS_TOKEN%}','获取模板通知列表'],
        'GET_ADD_TEMPLATE' =>['https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={%ACCESS_TOKEN%}','获取模板id'],
        'GET_USER_LIST' =>['https://api.weixin.qq.com/cgi-bin/user/get?access_token={%ACCESS_TOKEN%}&next_openid={%NEXT_OPENID%}','获取用户列表'],
    ];
    const CONFIG = [];

    protected static $access_token = '';//access_token

    /**
     * [http_request curl HTTP请求（支持HTTP/HTTPS，支持GET/POST）]
     * @Effect
     * @param  [type] $url  [地址]
     * @param  [type] $data [数据]
     * @return [type]       [description]
     */
    public static function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * [inject_check 自动过滤Sql的注入语句]
     * @Effect
     * @param  [type] $Sql_Str [需要过滤的数据]
     * @return [type]          [description]
     */
    public static function inject_check($Sql_Str)//。
    {

        if (!get_magic_quotes_gpc()) // 判断magic_quotes_gpc是否打开
        {
            $Sql_Str = addslashes($Sql_Str); // 进行过滤
        }
        $Sql_Str = str_replace("_", "_", $Sql_Str); // 把 '_'过滤掉
        $Sql_Str = str_replace("%", "%", $Sql_Str); // 把' % '过滤掉
        return $Sql_Str;
    }
    /**
     * [filter_mark 过滤英文标点符号 过滤中文标点符号]
     * @Effect
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    public static function filter_mark($text){
        if(trim($text)=='')return '';
        $text=preg_replace("/[[:punct:]\s]/",' ',$text);
        $text=urlencode($text);
        $text=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/",' ',$text);
        $text=urldecode($text);
        return trim($text);
    }

    /**
     * [get_user_info 获取用户基本信息]
     * @Effect
     * @param  [type] $openid [description]
     * @param  [type] $access_token [description]
     * @return mixed
     * @throws \jt\error\Exception
     */
    public static function get_user_info($openid,$access_token = null)
    {
        if($access_token == null){
            static::set_AccessToken();
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".static::$access_token."&openid=".$openid."&lang=zh_CN";
        $res = self::http_request($url);
        if(!json_decode($res, true)){
            throw new Exception($res);
        }
        return json_decode($res, true);
    }
    public static function subscribe($openid){
        return self::get_user_info($openid)['subscribe']??0;
    }

    /**
     * 批量获取用户信息
     * @param array  $openid
     * @param string $lang
     * @return mixed
     * @throws \jt\error\Exception
     */
    public static function infoBatchget(array $openid ,$lang = 'zh_CN')
    {

        if(count($openid) >100){
            throw new Exception('最大一次获取100条');
        }

        $openidData = [];
        foreach($openid as $value){
            $openidData[] = ['openid'=>$value,'lang'=>$lang];
        }
        $data = ['user_list'=>$openidData];
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token='.static::set_AccessToken();
        $result = self::http_request($url,json_encode($data));
        $result = json_decode($result,true);
        if(isset($result['errcode'])){
            var_dump($data);
            WechaErrorModel::open()->add(['name'=>'infoBatchget','log'=>json_encode($result),'request'=>json_encode($data)]);
            throw new Exception($result['errmsg']);
        }
        return $result;

    }

    /**
     * 获取用户(openid)列表
     * @param string $next_openid  拉取列表的最后一个用户的OPENID
     * @return mixed
     * @throws \jt\error\Exception
     */
    public static function getOpenidList($next_openid='')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.static::set_AccessToken().'&next_openid='.$next_openid;
        //https://api.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&next_openid=NEXT_OPENID
        $result = self::http_request($url);
        $result = json_decode($result,true);
        if(isset($result['errcode'])){
            WechaErrorModel::open()->add(['name'=>'infoBatchget','log'=>json_encode($result),'request'=>$next_openid]);
            throw new Exception($result['errmsg']);
        }
        return $result;
    }


    /**
     * 获取AccessToken
     */
    public static function set_AccessToken()
    {
        //判断并且获取AccessToken
        if(static::$access_token == ''){
            $AccessToken = new AccessToken();
            static::$access_token = $AccessToken->access_token();
            return static::$access_token;
        }
    }

    /**
     * @param $name  链接索引  如不存在 直接拼接$access_token成为URL
     * @param null $data  post 时的数据   默认null  存入自动post
     * @param bool $returntype  默认 返回json   如果为true 返回数组
     * @param bool $options  默认 编码中文   1为 不编码中文
     * @return mixed
     */
    public static function send($name, $data =null , $returntype = false,$options =0)
    {
        static::set_AccessToken();
        //判断url类型
        if(!isset(static::URL_ARR[$name][0])){
            $url = $name;
        }else{
            $url = static::URL_ARR[$name][0];
        }
        //拼接URL
        $url = $url.static::$access_token;
        //        var_dump(json_encode($data));
        //请求

        if($options == 0){
            $ApiData = self::http_request($url,json_encode($data));
        }else if($options ==1){
            $ApiData = self::http_request($url,json_encode($data,JSON_UNESCAPED_UNICODE ));

        }else if($options ==2){
            $ApiData = self::http_request($url,urldecode(json_encode($data,JSON_UNESCAPED_UNICODE )));
        }else  if($options == 3){
            $ApiData = self::http_request($url,$data);
        }else{
            throw new Exception('$options错误');
        }

        if($returntype){

            return json_decode($ApiData,true);
        }
        return $ApiData;
    }


    /**
     * 创建二维码
     * @param $scene_id 二维码id
     * @param int $expire_seconds  有效期 默认120 设置 0永久
     * @return mixed
     */
    public static function addQrcode($scene_id,$expire_seconds =120)
    {
        //     $action_name   二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
        if(is_int($scene_id) && $scene_id <20000000){
            $scene = 'scene_id';
            $expire_seconds == 0?$action_name = 'QR_LIMIT_SCENE':$action_name = 'QR_SCENE';
        }else{
            $expire_seconds == 0?$action_name = 'QR_LIMIT_STR_SCENE':$action_name = 'QR_STR_SCENE';
            $scene = 'scene_str';
            $scene_id = '"'.$scene_id.'"';
        }
        static::set_AccessToken();
        // 默认临时
        if($expire_seconds){
            //临时
            $qrcode = '{"expire_seconds": '.$expire_seconds.', "action_name": "'.$action_name.'", "action_info": {"scene": {"'.$scene.'": '.$scene_id.'}}}';
        }else{
            //永久
            $qrcode = '{"action_name": "'.$action_name.'", "action_info": {"scene": {"'.$scene.'": '.$scene_id.'}}}';
        }

        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".self::$access_token;
        $result = static::http_request($url,$qrcode);
        $jsoninfo = json_decode($result, true);
        $img = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$jsoninfo['ticket'];
        $jsoninfo['img'] = &$img;
        return $jsoninfo;
    }
    /**
     * @param $name  链接索引  如不存在 直接拼接$access_token成为URL
     * @param null $data  post 时的数据   默认null  存入自动post
     * @param bool $returntype  默认 返回json   如果为true 返回数组
     * @param bool $options  默认 编码中文   1为 不编码中文
     * @return mixed
     */
    public static function GetSend($name, $data =null , $returntype = false,$options =0)
    {
        static::set_AccessToken();
        //判断url类型
        if(!isset(static::URL_ARR[$name][0])){
            $url = $name;
        }else{
            $url = static::URL_ARR[$name][0];
        }
        $find[] = '{%ACCESS_TOKEN%}';
        $replace[] = static::$access_token;
        foreach ($data as $k=>$v){
            $find[] = '{%'.$k.'%}';
            $replace[] = $v;
        }
        $str = '';
        $strpl = str_replace($find,$replace,$url);
        exit;
        //拼接URL
        $ApiData = self::http_request($url);

        var_dump($ApiData);


        //请求
        //        if($options == 0){
        //            $ApiData = self::http_request($url,json_encode($data));
        //        }else if($options ==1){
        //            $ApiData = self::http_request($url,json_encode($data,JSON_UNESCAPED_UNICODE ));
        //
        //        }else if($options ==2){
        //            $ApiData = self::http_request($url,urldecode(json_encode($data,JSON_UNESCAPED_UNICODE )));
        //        }else  if($options == 3){
        //            $ApiData = self::http_request($url,$data);
        //        }else{
        //            throw new Exception('$options错误');
        //        }

        if($returntype){
            return json_decode($ApiData,true);
        }
        return $ApiData;
    }
}