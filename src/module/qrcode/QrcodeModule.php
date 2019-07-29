<?php
/**
 * Created by PhpStorm.
 * User: 84873
 * Date: 2018/8/28
 * Time: 15:58
 * @title 二维码事件处理
 */
namespace pizepei\wechat\module\qrcode;


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
        return ['content'=>'登录成功','reply_type'=>'text'];
    }
    /**
     * 入口
     */
    public function index()
    {

        /**
         * 读取二维码表
         */
        $Ticket= OpenWechatQrCodeModel::table()
            ->where([
                'ticket'=>$this->obj->Ticket,
                'authorizer_appid'=>$this->obj->config['authorizer_appid'],
                'scene_id'=>$this->obj->EventKey,
            ])
            ->fetch();
        if(empty($Ticket)){
//            return $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, '没有：1'.json_encode($data));
            return $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, '');
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