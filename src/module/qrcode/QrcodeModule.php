<?php
/**
 * Created by PhpStorm.
 * User: 84873
 * Date: 2018/8/28
 * Time: 15:58
 * @title 二维码事件处理
 */
namespace pizepei\wechat\module\qrcode;


use pizepei\helper\Helper;
use pizepei\service\websocket\Client;
use pizepei\wechat\model\OpenWechatCodeAppLog;
use pizepei\wechat\model\OpenWechatCodeAppModel;
use pizepei\wechat\model\OpenWechatQrCodeModel;
use pizepei\wechat\module\BaseModule;

class QrcodeModule extends BaseModule
{
    //平台类型0  c用户 1 pc后台  2 b端
    const TYPE = [
            '',//平台类型0
            '',//平台类型0
            '',//平台类型0
            '',//平台类型0
    ];

    /**
     * 注册验证
     * @param $Ticket
     * @return array
     */
    public function register($Ticket)
    {
        return ['content'=>'登录成功1','reply_type'=>'text'];
    }

    /**
     * 验证应用
     * @param $Ticket
     * @return array
     */
    public function codeApp($Ticket)
    {

        # 判断是否已经使用
        if ($Ticket['status'] !== '1'){
            return ['content'=>'二维码已经被使用'.$Ticket['status'].'3','reply_type'=>'text'];
        }
        $CodeAppLog = OpenWechatCodeAppLog::table($this->obj->config['authorizer_appid'])
            ->where([
                'qr_id'=>$Ticket['id'],
                'scene_id'=>$this->obj->EventKey,
            ])
            ->fetch();
        if (empty($CodeAppLog)){return ['content'=>'二维码已经被使用','reply_type'=>'text'];}
        if ($CodeAppLog['status'] !== '1'){return ['content'=>'二维码已经被使用!','reply_type'=>'text'];}
        # 获取app配置
        $CodeApp = OpenWechatCodeAppModel::table()
            ->where([
                'id'=>$CodeAppLog['appid'],
                'authorizer_appid'=>$this->obj->config['authorizer_appid']
            ])
            ->cache(['OpenWechatCodeApp','config'],60)
            ->fetch();
        # 根据app配置转发

        # 判断是否安全模式
        #   安全模式非直接WebSocket通知客户端结果，是直接在公众号中回复a连接<a href="https://bbbdo.ccc">点击确认</a>粉丝点击确认然后通知浏览器WebSocket通知客户端结果
        #       可以在确认后到自己的域名或者连接下

        # 推送 WebSocket
        # jwt 规则
        $Client = new Client([
            'data'=>[
                'uid'=>Helper::init()->getUuid(),
                'app'=>'codeApp',
            ],
        ]);
        $Client->connect();
        $res = $Client->sendUser($CodeAppLog['id'],
            ['type'=>'init','content'=>'您好','appid'=>$CodeAppLog['appid'],'data'>$CodeAppLog]
            ,true);

        return ['content'=>'登录成功<a href="https://www.bt.cn/invite">点击确认</a>','reply_type'=>'text'];
    }

    /**
     * 入口
     */
    public function index()
    {

        /**
         * 读取二维码表
         */
        $Ticket= OpenWechatQrCodeModel::table($this->obj->config['authorizer_appid'])
            ->where([
                'ticket'=>$this->obj->Ticket,
                'authorizer_appid'=>$this->obj->config['authorizer_appid'],
                'scene_id'=>$this->obj->EventKey,
            ])
            ->fetch();
        if(empty($Ticket)){
            return $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, '11');
        }

        $func = $Ticket['type'];
        $result = $this->$func($Ticket);
        if(empty($result)){return ;}
        switch($result['reply_type'])
        {
            case 'text'://文字回复
                return $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type,$result['content']);
                break;
            case 'news'://图文回复
                    $content = json_decode($result['content'], true);
                    //获取图文数量
                    $count = count($content);
                    $value = '';
                    foreach ($content as $k => $v) {
                        if(stripos($v['PicUrl'],'http')===false){
                            $v['PicUrl']= 'http:'.$v['PicUrl'];
                        }
                        $value .='<item>
                                        <Title><![CDATA['.$v['Title'].']]></Title>
                                        <Description><![CDATA['.$v['Description'].']]></Description>
                                        <PicUrl><![CDATA['.$v['PicUrl'].']]></PicUrl>
                                        <Url><![CDATA['.$v['Url'].']]></Url>
                                      </item>';
                    }
                    $news = str_replace('{$item}',$value,$this->obj->template_xml[$result['reply_type']]);
                    $content_text = sprintf($news,$this->obj->fromUsername,$this->obj->toUsername,$this->obj->time,$result['reply_type'],$count);
                    return $content_text;
                break;
            default:
        }

    }

}