<?php
/**
 * @Author: pizepei
 * @Date:   2017-06-12 21:24:25
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-05-13 10:59:19
 */

/**
 * 信息处理类
 * 微信消息接口
 * 包含微信扫描二维码登录
 * 扫描二维码绑定账号
 * 包括微信关键字回复
 * 语音回复等
 */

namespace pizepei\wechat\basics;

use enum\WxEventEnum;
use model\archives\ThirdCardModel;
use model\wechat\ChatBaseConfigModel;
use model\wechat\WechaErrorModel;
use pizepei\wechat\model\OpenWechatKeywordModel;
use pizepei\wechat\service\Config;
use service\BasicsPort\WeChat;
use utils\wechatbrief\func;
use model\wechat\KeywordModel;
use utils\wechatbrief\Module\Chat\BaseModel;
use utils\wechatbrief\Module\Chat\RedisModel;
use utils\wx\event\EventLogic;


class ReplyApi
{
    const  namespace = 'pizepei\wechat\module\\';
    const  namespacePath = '..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'pizepei'.DIRECTORY_SEPARATOR.'wechat'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'module'.DIRECTORY_SEPARATOR;

    private $postObj;//接受管理的xml对象

    //得到的是来源用户，是哪个用户跟我们发的消息$fromUsername$mediald
    private $fromUsername;

    //发给谁的。ToUserName   原始ID  开发者微信号
    private $toUsername;

    //被发送过来的内容
    private $Content;

    //休息类型
    private $msgtype;

    //unix时间戳
    private $time;

    //视频消息缩略图的媒体id
    private $ThumbMediaId = '';

    //媒体id
    private $mediald = '';

    //语音识别结果
    private $Recongnition = '';

    //图片网址
    private $picurl = '';

    //事件KEY值，与自定义菜单接口中KEY值对应
    private $EventKey = '';

    //事件类型，subscribe(订阅)、unsubscribe(取消订阅)等
    private $event = '';

    //二维码的ticket，可用来换取二维码图片
    private $Ticket = '';

    //地理位置纬度
    private $Latitude = '';

    //地理位置经度
    private $Longitude = '';

    //地理位置精度
    private $Precision = '';

    /**
     * 审核事件推送
     */
    //商户自己内部ID，即字段中的sid
    private $UniqId = '';

    //微信的门店ID，微信内门店唯一标示ID
    private $PoiId = '';

    //审核结果，成功succ 或失败fail
    private $Result = 'false';

    //成功的通知信息，或审核失败的驳回理由
    private $msg = '';

    /**
     * 加解密
     */
    //加密类型
    private $encrypt_type = null;
    //加解密
    private $WXBizMsgCrypt = null;
    //随机数
    private $nonce = null;
    //时间戳
    private $timeStamp = null;



    //关键字名字
    private $reply_name = '';
    //模块 模型名
    private $reply_model = '';
    //方法名称
    private $reply_method = '';
    //需要回复的内容 （部分模块会是用户发送来的内容）
    private $reply_content = '';
    //需要回复的消息类型
    private $reply_type = '';
    //提取的关键字
    private $keyword;

    //回复信息 使用的xml 模板 字符串
    private $template_Type;
    //--------------------------数据库存储方式-----------------------------
    public $config = '';

    //没有关注的的扫描二维码事件
    public $qrscene_ = '';

    function __construct($get, $config,$appid)
    {
        $this->config = $config;
        //消息接口token验证
        $SHALApi = new SHALApi($get,$this->config['token']);
        $SHALApi->control();
        //获取post变量
        $this->postObj = file_get_contents("php://input");
        //对信息进行解密
        if(isset($get['encrypt_type']) && $this->config['EncodingAESKey'] != ''){

            if($get['encrypt_type'] == 'aes'){

                $this->encrypt_type = $get['encrypt_type'];
                //实例化 加解密
                $WXBizMsgCrypt       = new WXBizMsgCrypt($this->config['token'], $this->config['EncodingAESKey'], $this->config['authorizer_appid']);
                $this->WXBizMsgCrypt = $WXBizMsgCrypt;

                $this->timeStamp = $get['timestamp'];
                $this->nonce     = $get['nonce'];
                $msg = '';
                $WXBizMsgCrypt->decryptMsg($get['msg_signature'], $get['timestamp'], $get['nonce'], $this->postObj, $msg);
                $this->postObj = $msg;
            }
        }
        //xml_todj()获取 xml并且 初始化 接收的成员属性
        //template_xml() 初始化 信息面板 成员属性
        $this->xml_todj();

    }
    /**
     * 魔术方法
     *
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        if(isset($this->$name)){
            return $this->$name;
        }

        return null;
    }

    /**
     * [xml_todj 获取 xml 对象]
     *
     * @Effect
     * @return [type] [description]
     */
    public function xml_todj()
    {


        if(!empty($this->postObj)){
            //这个语句直接百度的时候，查到的信息是做安全防御用的：对于PHP，由于simplexml_load_string 函数的XML解析问题出现在libxml库上，所以加载实体前可以调用这样一个函数，所以这一句也应该是考虑到了安全问题。
            libxml_disable_entity_loader(true);
            // simplexml_load_string() 函数把 XML 字符串载入对象中。
            // 如果失败，则返回 false。
            $postObj = simplexml_load_string($this->postObj, 'SimpleXMLElement', LIBXML_NOCDATA);
            //判断是否成功获取 xml对象
            if($postObj){
                // 赋值postObj成员属性
                $this->postObj = $postObj;
                //初始化成员属性
                $this->fromUsername = (string)$postObj->FromUserName;

                $this->toUsername = (string)$postObj->ToUserName;

                $this->Content = trim($postObj->Content);

                $this->msgtype = trim($postObj->MsgType);

                $this->time = time();

                $this->ThumbMediaId = $postObj->ThumbMediaId;

                $this->MediaId = trim($postObj->MediaId);

                $this->Recongnition = trim($postObj->Recognition, "!");

                $this->PicUrl = trim($postObj->PicUrl);

                $this->EventKey = (string)$postObj->EventKey;

                $this->event = $postObj->Event;

                $this->Ticket = $postObj->Ticket;

                $this->UniqId = $postObj->UniqId;
                $this->PoiId  = $postObj->PoiId;
                $this->Result = $postObj->result;

                $this->msg = $postObj->msg;

            }else{
                throw new \Exception('postObj null');
            }
            //返回
            return true;
        }
    }
    /**
     * [content_type 提取关键字 判断信息类型 处理内容]
     * @Effect
     * @return [type] [description]
     */
    function content_type()
    {

        switch($this->msgtype){
            case 'text'://文字回复

                $this->keyword = $this->Content;
                //数据库关键字
                $result = $this->keyword_trigger();

                break;

            case 'image'://图片

                $this->Content = $this->MediaId;
                //数据库关键字
                $result = $this->keyword_trigger();

                break;
            case 'voice'://语音


                $this->Content = $this->Recongnition;
                $this->keyword = $this->Recongnition;
                $result = $this->keyword_trigger();

                //                $this->voice();

                break;

            case 'video'://视频


                break;


            case 'event' ://事件

                switch($this->event){

                    //审核事件推送
                    case 'poi_check_notify':
                        $this->Content = 'Event_poi_check_notify';
                        $poiId = $this->PoiId;
                        //数据库关键字
                        $result = $this->keyword_trigger();
                        break;

                    //subscribe(订阅)、unsubscribe(取消订阅)
                    case 'subscribe':
                        //                     if(empty()){
                        //
                        WeChat::userSubscribe($this->fromUsername,1);
                        if(isset($this->EventKey) && !empty($this->EventKey)){
                            /**
                             * 没有关注公众号的扫描
                             */
                            //$this->Ticket = ltrim($this->Ticket, 'qrscene_');

                            $this->Ticket = str_replace("qrscene_","",$this->Ticket);
                            $this->EventKey = str_replace("qrscene_","",$this->EventKey);

                            //$this->EventKey = ltrim($this->EventKey, 'qrscene_');
                            $this->Content = 'SCAN_qrcode_EventKey';
                            $result = $this->keyword_trigger();

                            //$this->keyword_trigger($this->EventKey);
                        }else{

                            $this->Content = 'subscribe';
                            $result = $this->keyword_trigger();
                        }

                        break;


                    case 'unsubscribe':
                        WeChat::userSubscribe($this->fromUsername,0);

                        $this->Content = 'unsubscribe';
                        //数据库关键字
                        $result = $this->keyword_trigger();
                        break;


                    case 'CLICK':
                        //
                        //点击菜单拉取消息时的事件推送
                        //用户点击自定义菜单后，微信会把点击事件推送给开发者，请注意，点击菜单弹出子菜单，不会产生上报。
                        $this->Content = $this->EventKey; //todo:这里删除了拼接CLICK_

                        //数据库关键字
                        $result = $this->keyword_trigger();
                        //EventKey    事件KEY值，与自定义菜单接口中KEY值对应
                        break;


                    //点击菜单跳转链接时的事件推送
                    case 'VIEW':

                        //EventKey    事件KEY值，设置的跳转URL
                        $this->Content = 'unsubscribe';
                        $result = $this->keyword_trigger();
                        break;

                    // 扫描带参数二维码事件-------------------------用户未关注时，进行关注后的事件推送-----------------------//
                    //case 'subscribe': //1.
                    //
                    //    if(empty($this->Ticket)){
                    //        //                            fdfdhk
                    //        //没有  扫描事件  的关注事件
                    //        //$this->Content = 'subscribe';
                    //        //数据库关键字
                    //        $this->keyword_trigger();
                    //
                    //    }else{
                    //
                    //        //扫描二维码  并且没有关注公众号
                    //        //$this->Ticket = ltrim($this->Ticket, 'qrscene_');
                    //        $this->Ticket = str_replace("qrscene_","",$this->Ticket);
                    //        $this->keyword_trigger($this->EventKey);
                    //        // $this->qrscene_ = 'qrscene_';
                    //        //                                $this->subscribe();
                    //    }
                    //    // $this->Content = '关注事件';
                    //    // EventKey    事件KEY值，qrscene_为前缀，后面为二维码的参数值
                    //    // Ticket  二维码的ticket，可用来换取二维码图片
                    //    break;

                    case 'SCAN': //2. 用户已关注时的事件推送 (包括二维码)
                        $this->EventKey = str_replace("qrscene_","",$this->EventKey);

                        //$this->EventKey = ltrim($this->EventKey, 'qrscene_');
                        //$this->Ticket = ltrim($this->Ticket, 'qrscene_');
                        $this->Ticket = str_replace("qrscene_","",$this->Ticket);
                        $this->Content = 'SCAN_qrcode_EventKey';
                        $result = $this->keyword_trigger();
                        // EventKey    事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
                        // Ticket  二维码的ticket，可用来换取二维码图片
                        break;

                    //上报地理位置事件
                    case 'LOCATION':
                        // Latitude    地理位置纬度
                        // Longitude   地理位置经度
                        // Precision   地理位置精度
                        $this->Content = 'unsubscribe';
                        $result = $this->keyword_trigger();

                        break;
                };

                break;

            default:
        }
        return $result;
    }

    /**
     * 触发 关键字 函数
     *
     * @param string $type 关键字名称
     * @param string $contents 内容
     */
    function keyword_trigger($content = '', $type = 'text')
    {
        $this->reply_type = $type;
        //获取关键字
        if(empty($content)){
            $sql_keyword = $this->Content;//定义查询关键字
            //查询关键字
            if(isset(BasicsConst::sys_keyword[$sql_keyword])){
                //系统关键字
                $result = BasicsConst::sys_keyword[$sql_keyword];
            }else{
                //从数据库查询关键字
                $result = OpenWechatKeywordModel::table()->where(
                    [
                    'authorizer_appid'=>$this->config['authorizer_appid'],
                    'component_appid'=>$this->config['component_appid'],
                    'status'=>10,
                    ]
                )->fetch();
                if (empty($result)){
                    /**
                     * 模糊匹配
                     * select id,name,length(name),length(replace(name,'爱','')) from `open_wechat_keyword`  where  `name` LIKE '%爱%'  order by length(replace(name,'爱',''))
                     */
                }
            }
            if(isset($result['name'])){
                //获取关键字 参数
                $this->reply_name    = $result['name'];
                $this->reply_model   = $result['model'];
                $this->reply_method  = $result['method'];
                $this->reply_type    = $result['type'];
                $this->reply_content = $result['content'];
                //部分模块不需要现在定义回复内容，数据库在无内容
                if(empty($this->reply_content)){
                    $this->reply_content = '';
                }
            }else{
                /**
                 * 木有
                 */
                return '';
            }
        }else{
            /**
             * 自定义关键字
             */
            $this->reply_name    = 'name';
            $this->reply_model   = 'keyword';
            $this->reply_method  = 'index';
            $this->reply_content = $content;
        }
        //匹配回复   信息模板
        $this->template_Type = BasicsConst::template_xml[$this->reply_type];
        /**
         * 这里设置检查   模块类是否存在
         */
        if(!file_exists(self::namespacePath.$this->reply_model.DIRECTORY_SEPARATOR.ucfirst($this->reply_model).'Module.php')){
            throw new \Exception('模块不存在:'.$this->reply_model.DIRECTORY_SEPARATOR.ucfirst($this->reply_model).'Module.php');
        }
        /**
         * 实例化
         */
        $className = self::namespace.lcfirst($this->reply_model).'\\'.ucfirst($this->reply_model).'Module';
        $new = new $className($this);
        /**
         * 判断方法是否存在
         */
        if (!method_exists($new,$this->reply_method)){
            throw new \Exception($className.':'.$this->reply_method.'不存在');
        }
        $method =& $this->reply_method;
        /**
         * 使用方法进行回复
         */
        $replyMsg = $new->$method();
        /**
         * 替换返回的html br 为  PHP_EOL
         */
        if ($this->reply_type == 'text'){
            $replyMsg = str_replace("<br>", PHP_EOL, $replyMsg);
        }
        /**
         *判断是否需要见面：加密处理
         */
        if($this->encrypt_type){
            $this->WXBizMsgCrypt->encryptMsg($replyMsg, $this->timestamp, $this->nonce, $replyMsg);
            return  $replyMsg;
        }else{
            return $replyMsg;
        }
    }

    //语音识别 选择处理
    function voice()
    {

    }

    //绑定
    public function subscribe()
    {

    }

    //获取用户基本信息
    public function get_user_info($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = func::http_request($url);

        return json_decode($res, true);
    }

    /**
     * [inject_check 自动过滤Sql的注入语句]
     *
     * @Effect
     *
     * @param  [type] $Sql_Str [需要过滤的数据]
     *
     * @return [type]          [description]
     */
    public function inject_check($Sql_Str)//。
    {

        if(!get_magic_quotes_gpc()) // 判断magic_quotes_gpc是否打开
        {
            $Sql_Str = addslashes($Sql_Str); // 进行过滤
        }
        $Sql_Str = str_replace("_", "_", $Sql_Str); // 把 '_'过滤掉
        $Sql_Str = str_replace("%", "%", $Sql_Str); // 把' % '过滤掉

        $check = preg_match("/select|insert|update|;|delete|'|\*|*|../|./|union|into|load_file|outfile/i", $Sql_Str);
        if($check){
            return '非法关键字';
            //echo '<script language="JavaScript">alert("系统警告：nn请不要尝试在参数中包含非法字符尝试注入！");</script>';
            exit();
        }else{
            return $Sql_Str;
        }
    }
    /**
     * 向某个客户端连接发消息
     *
     * @param int    $client_id
     * @param string $message
     *
     * @return bool
     */
    public function sendToClient($client_id, $message)
    {
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';

        return Gateway::sendToClient($client_id, $message);
    }


}
