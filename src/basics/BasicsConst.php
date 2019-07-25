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
            'model'       => 'Qrcode',          //模型名称（模块）
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
}