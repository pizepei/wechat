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
use model\wechat\KeywordLogModel;
use model\wechat\WechaErrorModel;
use service\BasicsPort\WeChat;
use utils\wechatbrief\func;
use model\wechat\KeywordModel;
use utils\wechatbrief\Module\Chat\BaseModel;
use utils\wechatbrief\Module\Chat\RedisModel;
use utils\wx\event\EventLogic;


class ReplyApi
{

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

    /**
     * 关键字数据(系统级别)
     */
    const sys_keyword = [
        'SCAN_qrcode_EventKey'=>[
            'title'       => '二维码事件',          //规则名称
            'name'        => 'SCAN_qrcode_EventKey',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'Qrcode',          //模型名称（模块）
            'method'      => 'index',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'Event_poi_check_notify'=>[
            'title'       => '门店审核',          //规则名称
            'name'        => 'Event_poi_check_notify',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'Store',          //模型名称（模块）
            'method'      => 'index',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '1111',                //回复内容
        ],

        'Kf'=>[
            'title'       => '客服',          //规则名称
            'name'        => 'Kf',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'chat',          //模型名称（模块）
            'method'      => 'ascertainv',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'KF'=>[
            'title'       => '客服',          //规则名称
            'name'        => 'KF',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'chat',          //模型名称（模块）
            'method'      => 'ascertainv',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'kf'=>[
            'title'       => '客服',          //规则名称
            'name'        => 'kf',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'chat',          //模型名称（模块）
            'method'      => 'ascertainv',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'Q'=>[
            'title'       => '客服',          //规则名称
            'name'        => 'Q',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'chat',          //模型名称（模块）
            'method'      => 'finishCaht',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'q'=>[
            'title'       => '客服',          //规则名称
            'name'        => 'Q',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'chat',          //模型名称（模块）
            'method'      => 'finishCaht',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'openid'=>[
            'title'       => '获取openid',          //规则名称
            'name'        => 'openid',          //关键字
            'matchType'   => '10',                //10全匹配,20模糊匹配
            'model'       => 'keyword',          //模型名称（模块）
            'method'      => 'getOpenid',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],

    ];

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

    //----------------回复信息需要的 成员属性---------------------------------------

    //text 文字  image 图片  news 图文模板
    //信息  模板  array
    private $template_xml = [
        'text' => '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        <FuncFlag>0</FuncFlag>
        </xml>',

        'image' => '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Image>
        <MediaId><![CDATA[%s]]></MediaId>
        </Image>
        </xml>',

        'news' => '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <ArticleCount>%s</ArticleCount>
            <Articles>
                {$item}
            </Articles>
        </xml>',

    ];

    //提取的关键字
    private $keyword;

    //回复信息 使用的xml 模板 字符串
    private $template_Type;
    //--------------------------数据库存储方式-----------------------------
    public $config = '';

    //没有关注的的扫描二维码事件
    public $qrscene_ = '';

    function __construct($get = null, $config = null)
    {


        //消息接口token验证
        $SHALApi = new SHALApi($get);
        $SHALApi->control();
        //获取post变量
        // $this->postObj = $GLOBALS["HTTP_RAW_POST_DATA"];
        $this->postObj = file_get_contents("php://input");
        if($config == null){
            //没有  定义自定义配置使用系统定义配置
            $this->config = \Config::WECHAT_CONFIG;


        }else{
            //有自定义配置
            $this->config = $config;
        }
        //WechaErrorModel

        WechaErrorModel::open()->add(['name'=>'config','log'=>json_encode($this->config),'request'=>$this->postObj]);
        //对信息进行解密
        if(isset($get['encrypt_type']) && $this->config['encodingAesKey'] != ''){

            if($get['encrypt_type'] == 'aes'){

                $this->encrypt_type = $get['encrypt_type'];
                //实例化 加解密
                $WXBizMsgCrypt       = new WXBizMsgCrypt($this->config['token'], $this->config['encodingAesKey'], $this->config['appid']);
                $this->WXBizMsgCrypt = $WXBizMsgCrypt;

                $this->timeStamp = $get['timestamp'];
                $this->nonce     = $get['nonce'];

                $msg = '';
                $WXBizMsgCrypt->decryptMsg($get['msg_signature'], $get['timestamp'], $get['nonce'], $this->postObj, $msg);
                $this->postObj = $msg;

            }
        }

        //       Logger::logToTable('event_'.Sundry::randomStr(6),[json_encode($this->postObj)],true);
        //xml_todj()获取 xml并且 初始化 接收的成员属性
        //template_xml() 初始化 信息面板 成员属性
        $this->xml_todj();
        $isCardEvent = false;

        try{
            $this->eventByCard();
            $isCardEvent = true;
        }catch(\Exception $e){

        }
        if(!$isCardEvent){
            WechaErrorModel::open()->add(['name'=>'content_type','log'=>json_encode($this->postObj),'request'=>$this->postObj]);
            $this->content_type();//提取关键字
            if(\Config::WX_EVENT_FALLBACK_URL){
                $this->proxyMessage(\Config::WX_EVENT_FALLBACK_URL);
            }
        }
    }

    protected function proxyMessage($url)
    {
        $ci = curl_init();
        curl_setopt_array($ci, [
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_HTTPHEADER      => [],
            CURLOPT_HEADER          => 0,        // 1:获取头部信息
            CURLOPT_RETURNTRANSFER  => 1,        // 1:不直接输出
            CURLOPT_POSTFIELDS      => $this->postObj,    // post数据
            CURLOPT_URL             => $url.'?'.$_SERVER['QUERY_STRING'],
            CURLOPT_CUSTOMREQUEST   => $_SERVER['REQUEST_METHOD'],
            CURLOPT_ACCEPT_ENCODING => 'gzip',
        ]);
        curl_exec($ci);
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
     * 和会员卡有关的消息处理
     */
    private function eventByCard()
    {
        $input = json_decode(json_encode($this->postObj), true);
        if(isset($input['CardId']) && isset($input['MsgType']) && $input['MsgType'] == 'event' && WxEventEnum::keyExist($input['Event'])){
            $thirdCardInfo = ThirdCardModel::open()->equalsMulti([
                'thirdCardId' => $input['CardId'],
                'type'        => 'wechat',
            ])->first();

            if(empty($thirdCardInfo)){
                throw new \Exception('Noting to do');
            }

            $eventLogic = new EventLogic($input['Event'], $input);
            $eventLogic->callFunc('act');

        }else{
            throw new \Exception('Nothing to do');
        }
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
                //写入错误日志 mt_rand(0,500)
                file_put_contents('../utils/wechatbrief/Module/Cache/ReplyApi_log'.date('ymd_h').'.txt',
                    '['.date('y_m_d H').json_encode($this->postObj).']\n', FILE_APPEND);
                exit('非法请求');
            }


            //返回
            return true;
        }
    }

    /**
     * [content_type 提取关键字 判断信息类型 处理内容]
     *
     * @Effect
     * @return [type] [description]
     */
    function content_type()
    {

        switch($this->msgtype){
            case 'text'://文字回复

                $this->keyword = $this->Content;

                //数据库关键字
                $this->keyword_trigger();

                break;

            case 'image'://图片

                $this->Content = $this->MediaId;
                //数据库关键字
                $this->keyword_trigger();

                break;
            case 'voice'://语音


                $this->Content = $this->Recongnition;
                $this->keyword = $this->Recongnition;
                $this->keyword_trigger();

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
                        $this->keyword_trigger();
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
                            $this->keyword_trigger();

                            //$this->keyword_trigger($this->EventKey);
                        }else{

                            $this->Content = 'subscribe';
                            $this->keyword_trigger();
                        }

                        break;


                    case 'unsubscribe':
                        WeChat::userSubscribe($this->fromUsername,0);

                        $this->Content = 'unsubscribe';
                        //数据库关键字
                        $this->keyword_trigger();
                        break;


                    case 'CLICK':
                        //
                        //点击菜单拉取消息时的事件推送
                        //用户点击自定义菜单后，微信会把点击事件推送给开发者，请注意，点击菜单弹出子菜单，不会产生上报。
                        $this->Content = $this->EventKey; //todo:这里删除了拼接CLICK_

                        //数据库关键字
                        $this->keyword_trigger();
                        //EventKey    事件KEY值，与自定义菜单接口中KEY值对应
                        break;


                    //点击菜单跳转链接时的事件推送
                    case 'VIEW':

                        //EventKey    事件KEY值，设置的跳转URL
                        $this->Content = 'unsubscribe';

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
                        $this->keyword_trigger();
                        // EventKey    事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
                        // Ticket  二维码的ticket，可用来换取二维码图片

                        break;

                    //上报地理位置事件
                    case 'LOCATION':
                        // Latitude    地理位置纬度
                        // Longitude   地理位置经度
                        // Precision   地理位置精度
                        $this->Content = 'unsubscribe';

                        break;

                };

                break;

            default:
        }
    }

    /**
     * 触发 关键字 函数
     *
     * @param string $type 关键字名称
     * @param string $contents 内容
     */
    function keyword_trigger($content = '', $type = 'text')
    {

        WechaErrorModel::open()->add(['name'=>'keyword_trigger','log'=>json_encode(['content'=>$content,'type'=>$type]),'request'=>$this->postObj]);

        $this->reply_type = $type;

        //获取关键字
        if(empty($content)){
            //            获取关键字
            $sql_keyword = $this->Content;
            //file_put_contents('sql_keyword.txt',$sql_keyword);

            // 判断数据库存储类型
            if($this->config['cache_keyword_type'] == 'mysql'){
                //查询关键字
                if(isset(self::sys_keyword[$sql_keyword])){
                    $result = self::sys_keyword[$sql_keyword];
                }else{
                    $result = KeywordModel::getKeyword($sql_keyword);
                }
                $logModel = KeywordLogModel::open();
                $logModel->add([
                    'keywordId' => $result['id'] ?? 0, //关键词ID
                    'content'   => $sql_keyword, //接收内容
                    'openid'    => $this->fromUsername, //发送人
                ]);

                if($result){
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

                    if(!empty(ChatBaseConfigModel::open()->equals('status',22)->first())){
                        //不在系统关键字范围
                        //判断是否已经存在客服会话缓存信息
                        //$RedisModel = new BaseModel();
                        $RedisModel = new RedisModel();
                        if($RedisModel->get_session($this->fromUsername)){
                            $this->reply_name    = 'name';
                            $this->reply_model   = 'chat';
                            $this->reply_method  = 'chat';
                            $this->reply_content = $this->Content;
                        }else{

                            //不在会话中
                            if($RedisModel->getAscertain($this->fromUsername) == 1){

                                $this->reply_name    = 'name';
                                $this->reply_model   = 'chat';
                                $this->reply_method  = 'ascertainv';
                                $this->reply_content = $this->Content;

                            }else{
                                $this->reply_name    = 'name';
                                $this->reply_model   = 'keyword';
                                $this->reply_method  = 'index';
                                $this->reply_content = '回复KF进入在线客服系统。'.PHP_EOL.'回复Q退出客服系统。'.$RedisModel->getAscertain($this->fromUsername);

                            }
                        }
                    }else{
                        return '';
                    }

                }
            }else{
                if($this->config == 'redis'){


                }
            }
            //
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
        $this->template_Type = $this->template_xml[$this->reply_type];
        /**
         * 这里设置检查   模块类是否存在
         * 不存在  写入日志
         */
        if(!file_exists('../utils/wechatbrief/Module/'.ucfirst($this->reply_model).'/'.ucfirst($this->reply_model).'Module.php')){
            file_put_contents('../utils/WechatBrief/Module/Cache/LOG_module'.date('ymd_h').'.txt',
                '[类名称]./module/'.$this->reply_model.'/'.ucfirst($this->reply_model).'Module.class.php'.'[关键字]'.$this->reply_name, FILE_APPEND);
            exit();
        }
        //声明模块类
        $new1 = '\utils\wechatbrief\Module\\'.ucfirst($this->reply_model).'\\'.ucfirst($this->reply_model).'Module';
        //file_put_contents('aModule.txt',json_encode($new1));

        $new = new $new1;
        //回复信息      =   调用$method（）模块中的方法 处理返回的 完整xml内容  echo 到微信
        $method   = $this->reply_method;
        $replyMsg = $new->$method($this);
        $replyMsg = str_replace("<br>", PHP_EOL, $replyMsg);
        /**
         *加密处理
         */
        if($this->encrypt_type){
            $this->WXBizMsgCrypt->encryptMsg($replyMsg, $this->timeStamp, $this->nonce, $replyMsg);
            //file_put_contents('replyMsg.txt', $replyMsg);
            //file_put_contents('Config.txt', json_encode(\Config::WECHAT_CONFIG));
            echo $replyMsg;
        }else{

            echo $replyMsg;
        }
        WechaErrorModel::open()->add(['name'=>'replyMsg','log'=>json_encode($replyMsg),'request'=>$replyMsg]);

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
