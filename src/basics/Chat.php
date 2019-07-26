<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/26
 * Time: 14:00
 * @title 聊天、客服
 */
namespace pizepei\wechat\basics;


class Chat
{
    /**
     * @Author 皮泽培
     * @Created 2019/7/26 17:09
     * @title  获取客服列表
     * @explain 获取客服列表
     * @throws \Exception
     */
    public function chatList()
    {

    }
    # 使用客服接口主动发送消息给粉丝
    public function chatSend()
    {
//        'KF_SEND_V1'=>['https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={%ACCESS_TOKEN%}','客服向客户发送信息'],
    }
    # 消息记录
    public function chattingRecords()
    {

    }
}