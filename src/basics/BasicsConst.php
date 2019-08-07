<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/12
 * Time: 14:00
 * @title 系统关键字const类
 */

namespace pizepei\wechat\basics;


class BasicsConst
{
    public function __get($name)
    {
        return self::$template_xml;
        // TODO: Implement __get() method.
    }

    /**
     * 关键字数据(系统级别)
     */
    const sys_keyword = [
        'SCAN_qrcode_EventKey'=>[
            'title'       => '二维码事件',          //规则名称
            'name'        => 'SCAN_qrcode_EventKey',          //关键字
            'match_type'   => '10',                //10全匹配,20模糊匹配
            'module_source'   => 'defaultSource',   //模块来源'defaultSource' or 'customSource'
            'model'       => 'qrcode',          //模型名称（模块）
            'method'      => 'index',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],
        'openid'=>[
            'title'       => '获取openid',          //规则名称
            'name'        => 'openid',          //关键字
            'match_type'   => '10',                //10全匹配,20模糊匹配
            'module_source'   => 'defaultSource',   //模块来源'defaultSource' or 'customSource'
            'model'       => 'keyword',          //模型名称（模块）
            'method'      => 'getOpenid',          //模型方法名称
            'type'        => 'text',          //回复类型
            'status'      => '10',                //是否生效 10生效 20不生效
            'content'     => '',                //回复内容
        ],

    ];
    /**
     * 模板通知
     */
    const TEMPLATE = [
        'OPENTM407316934' => [
            'templateID'       => 'OPENTM407316934',
            "title"            => "流程待办提醒",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "注意了——有用户发起在线咨询\n", 'color' => "#000000",],
                'keyword1' => ['value' => 'data', 'color' => '#000093',],
                'keyword2' => ['value' => 'data', 'color' => '#000093',],
                'keyword3' => ['value' => 'data', 'color' => '#000093',],
                'keyword4' => ['value' => 'data', 'color' => "#000093",],
                'remark'   => ['value' => 'data', 'color' => '#000093',],
            ],
        ],
        'OPENTM406411654' => [
            'templateID'       => 'OPENTM406411654',
            "title"            => "订单取消通知",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => 'data', 'color' => '#000093',],
                'keyword2' => ['value' => 'data', 'color' => '#000093',],
                'remark'   => ['value' => 'data', 'color' => '#000093',],
            ],
        ],
        'OPENTM202521011' => [
            'templateID'       => 'OPENTM202521011',
            "title"            => "订单完成通知",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => "data", 'color' => "#000000",],
                'keyword2' => ['value' => "data", 'color' => "#000000",],
                'remark'   => ['value' => "data", 'color' => "#000000",],
            ],
        ],
        'OPENTM412319459' => [
            'templateID'       => 'OPENTM412319459',
            "title"            => "核销成功通知",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => 'data', 'color' => '#000093',],
                'keyword2' => ['value' => 'data', 'color' => '#000093',],
                'keyword3' => ['value' => 'data', 'color' => '#000093',],
                'keyword4' => ['value' => 'data', 'color' => "#000093",],
                'remark'   => ['value' => 'data', 'color' => '#000093',],
            ],
        ],
        'OPENTM407734422' => [
            'templateID'       => 'OPENTM407734422',
            "title"            => "取货超时提醒",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => "data", 'color' => "#000000",],
                'keyword2' => ['value' => "data", 'color' => "#000000",],
                'keyword3' => ['value' => "data", 'color' => "#000000",],
                'keyword4' => ['value' => "data", 'color' => "#000000",],
                'remark'   => ['value' => "data", 'color' => "#000000",],
            ],
        ],
        'OPENTM412581791' => [
            'templateID'       => 'OPENTM412581791',
            "title"            => "客户到店通知",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => "data", 'color' => "#000000",],
                'keyword2' => ['value' => "data", 'color' => "#000000",],
                'keyword3' => ['value' => "data", 'color' => "#000000",],
                'keyword4' => ['value' => "data", 'color' => "#000000",],
                'keyword5' => ['value' => "data", 'color' => "#000000",],
                'remark'   => ['value' => "data", 'color' => "#000000",],
            ],
        ],
        'OPENTM414338361' => [
            'templateID'       => 'OPENTM414338361',
            "title"            => "消费成功通知",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "互联网|电子商务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => 'data', 'color' => '#000000',],
                'keyword2' => ['value' => 'data', 'color' => '#000000',],
                'keyword3' => ['value' => 'data', 'color' => '#000000',],
                'keyword4' => ['value' => 'data', 'color' => "#000000",],
                'remark'   => ['value' => 'data', 'color' => '#5891df',],
            ],
        ],
        'OPENTM208001772' => [
            'templateID'       => 'OPENTM208001772',
            "title"            => "流程待办提醒",
            "primary_industry" => "IT科技",
            "deputy_industry"  => "IT软件与服务",
            "data"             => [
                'first'    => ['value' => "data", 'color' => "#000000",],
                'keyword1' => ['value' => 'data', 'color' => '#000000',],
                'keyword2' => ['value' => 'data', 'color' => '#000000',],
                'keyword3' => ['value' => 'data', 'color' => '#000000',],
                'keyword4' => ['value' => 'data', 'color' => "#000000",],
                'remark'   => ['value' => 'data', 'color' => '#5891df',],
            ],
        ],
    ];

    const QrcodeType = [
        0=>'codeApp',
        1=>'register',
    ];


    //----------------回复信息需要的 成员属性---------------------------------------
    //text 文字  image 图片  news 图文模板
    //信息  模板  array
    const template_xml = [
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
    /**
     * codeapp
     * 安全确认模式下的模板
     */
    const codeAppTemplate = [
        'url_verify'=>[
            'template'=>'您正在进行{{behavior}}授权操作<br><a href="{{url}}">点击确认</a>',//通知模板
            'templateData'=>[
                'url'=>'http://oauth.heil.top/wechat/common/code-app/url-affirm',//同意授权页面
            ],
        ],
    ];
}