<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/7/12
 * Time: 17:36
 * @title 微信相关请求类
 */

namespace pizepei\wechat\service;


class HttpRequest
{
    /**
     * 微信接口错误代码
     */
    const error_code = [

    ];
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
}