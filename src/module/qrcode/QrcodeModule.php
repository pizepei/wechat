<?php
/**
 * Created by PhpStorm.
 * User: 84873
 * Date: 2018/8/28
 * Time: 15:58
 * @title 二维码事件处理
 */
namespace pizepei\wechat\module\qrcode;

use jt\Model;
use model\archives\AccountModel;
use model\archives\ThirdAccount;
use model\log\ThirdRecommendBind;
use model\openExtension\OpenExtensionAppModel;
use model\wechat\KeywordLogModel;
use model\wechat\KeywordModel;
use model\wechat\Ticket;
use model\wechat\ChaserviceModel;
use model\manage\Organization;
use model\wechat\WechaErrorModel;
use service\openExtension\OpenExtensionUser;
use service\tenant\TenantAuthorizeBiz;
use utils\wechatbrief\func;
use utils\wechatbrief\Module\Keyword\KeywordModule;
use utils\wechatbrief\Port\AccessToken;
use utils\wx\common\WechatBase;

class QrcodeModule
{
    //平台类型0  c用户 1 pc后台  2 b端
    const TYPE = [
            '',//平台类型0
            '',//平台类型0
            '',//平台类型0
            '',//平台类型0

    ];
    //二维码类型0未知  1 绑定微信客服（专属） 2  绑定微信客服（常规） 3邀请关注绑定关系(店铺) 4 邀请关注绑定关系(员工)'
    const MTYPE = [
        '',//二维码类型0未知
        'setchat',//绑定微信客服
        'setchat',// 2  绑定微信客服（常规）
        'InviteAttentionShop',//3邀请关注绑定关系(店铺)
        'InviteAttentionStaff',//4邀请关注绑定关系(员工)
        'targetUrl',//5 通知第三方
    ];
    /**
     * 入口
     * @param $obj
     */
    public function index($obj)
    {


        /**
         * 读取二维码表
         */
        $Ticket = Ticket::setQrcode($obj->Ticket,$obj->EventKey,$obj->config['authorizer_appid']);

        WechaErrorModel::open()->add(['name'=>'setQrcode','log'=>json_encode($Ticket),'request'=>json_encode([$obj->Ticket,$obj->EventKey])]);

        if(empty($Ticket)){
            return $content_text = sprintf($obj->template_Type, $obj->fromUsername, $obj->toUsername, $obj->time, $obj->reply_type, '没有：'.$obj->EventKey);
        }

        $func = self::MTYPE[$Ticket['Type']];
        $result = $this->$func($obj,$Ticket);
        WechaErrorModel::open()->add(['name'=>'setQrcodeResult','log'=>json_encode($result),'request'=>json_encode([$obj->Ticket,$obj->EventKey])]);
        if(empty($result)){return ;}
        switch($result['reply_type'])
        {
            case 'text'://文字回复
                return $content_text = sprintf($obj->template_Type, $obj->fromUsername, $obj->toUsername, $obj->time, $obj->reply_type,$result['content']);
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
                    $news = str_replace('{$item}',$value,$obj->template_xml[$result['reply_type']]);
                    $content_text = sprintf($news,$obj->fromUsername,$obj->toUsername,$obj->time,$result['reply_type'],$count);
                    return $content_text;
                break;
            default:
        }

    }

    /**
     *通知第三方
     *
     * @throws \jt\error\Exception
     * @throws \Exception
     */
    protected function targetUrl($obj,$Ticket)
    {
        if($Ticket['status'] == 1){
            return ['content'=>'二维码已经被使用','reply_type'=>'text'];
        }
        $resData['ticket'] = [
            'content'=>$Ticket['content'],
            'url'=>$Ticket['url'],
            'ticketid'=>$Ticket['ticketid'],
            'code_url'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$Ticket['ticketid'],
            'wxAppid'=>$Ticket['wxAppid'],
            'thirdAppid'=>$Ticket['thirdAppid'],
            'createAt'      =>$Ticket['createAt'],//创建时间
            'updateAt'      => $Ticket['updateAt'],//更新时间
            'explain'=>[
                'content'=>'本次二维码唯一标识',
                'url'=>'二维码内容可用来生成二维码',
                'code_url'=>'微信官方显示二维码连接',
                'wxAppid'=>'微信公众号appid',
                'thirdAppid'=>'应用appid',
                'ticketid'=>'微信官方二维码id',
                'createAt'      =>'创建时间',
                'updateAt'      => '更新时间',
            ]
        ];
        /**
         * 通过appid查询对应的配置
         */
        $App = OpenExtensionAppModel::open();
        $config = $App->equals('appid',$Ticket['thirdAppid'])->equals('status',1)->first();
        if(empty($config))
        {
            return ['content'=>'app不存在','reply_type'=>'text'];
        }
        /**
         *通过
         * openid
         * appid
         */
        $data = TenantAuthorizeBiz::getGroupWx($obj->config['authorizer_appid'],'wxAppid');
        if(!isset($data['groupId']))
        {
            return ['content'=>'信息不存在','reply_type'=>'text'];
        }
        /**
         * 修改二维码状态
         */
        Ticket::open()->equals('id',$Ticket['id'])->edit(['status'=>1]);
        //$AccessToken  = new AccessToken();
        //$this->access_token = $AccessToken->access_token();
        /**
         * 获取微信信息
         */
        $userInfo = func::get_user_info($obj->fromUsername);
        $resData['actionType'] = 'WeChatAuthorization';
        $resData['WeChatInfo'] = $userInfo;
        $resData['Binding'] = false;
        /**
         * $obj->fromUsername
         */
        // 判断是否已经绑定过
        $bindInfo = (ThirdAccount::open())->getBindInfo([
            'type'  => 'wechat',
            'appid'  => $obj->config['authorizer_appid'],
            'value' => $obj->fromUsername,
        ]);
        if(isset($bindInfo['customerId'])){
            $Account = AccountModel::open()->equals('customerId',$bindInfo['customerId'])->first('id,mobile');
            if(empty($Account)){
                /**
                 * 异常请联系客服
                 */
                return ['content'=>'微信绑定异常请联系客服','reply_type'=>'text'];
            }
            /**
             * 有，获取账号信息
             */
            $Open = new OpenExtensionUser();
            $res = $Open->memberLogon([
                'source'=>'E-Shop',
                'actionType'=>'memberLogon',
                'data'=>[
                    'mobile'=>$Account['mobile'],//会员手机号码
                    'StoreId'=>$Ticket['thirdStoreId'],//固定的店铺id
                ]
            ],$config,false);
            if(isset($res['error'])){
                return ['content'=>$res['error'],'reply_type'=>'text'];
            }
        }else{
            $Open = new OpenExtensionUser();
            $resData = $Open::encrypt($config,$resData);
            func::http_request($Ticket['targetUrl'],$resData);
            /**
             * 需要绑定
             */
            return ['content'=>'请绑定账号','reply_type'=>'text'];
        }
        /**
         * 成功
         */
        $resData['Binding'] = true;

        $res = array_merge(['Account'=>$res],$resData);
        $res = $Open::encrypt($config,$res);
        func::http_request($Ticket['targetUrl'],$res);

        return ['content'=>'登录成功','reply_type'=>'text'];



    }

    /**
     * 设置客服
     */
    protected function setchat($obj,$Ticket)
    {

        /**
         * 读取二维码表
         *
         * 整理事件
         *
         *
         * 创建客服
         */
        if(empty($Ticket)){  $content = '没有意义的二维码'; }
        if($Ticket['status'] == 1){
            $content = '二维码已经被使用';
            $content_text = sprintf($obj->template_Type, $obj->fromUsername, $obj->toUsername, $obj->time, $obj->reply_type, $content);
            return $content_text;
        }
        /**
         * 获取岗位+员工信息
         */
        $Organization = Organization::getPositionStaff($Ticket['positionId']);
        $data = [
            'openid' => $obj->fromUsername,
            'employeeid'=>$Ticket['employeeid'],
            'storeId'=>$Ticket['storeId'],
            'positionId'=>$Ticket['positionId'],
            'pic'=>$Organization['Staff']['avatar'],//客服头像
            'positionName'=>$Organization['Position']['name'],//岗位名称
            'name'=>$Organization['Staff']['name'],//客服昵称
        ];
        $ChaserviceModel = ChaserviceModel::addChaservice($data);
        if(!$ChaserviceModel){ return '重复添加';}
        if(isset($ChaserviceModel['insertId'])){
            return $Organization['Staff']['name'].'您好'.PHP_EOL.'成功绑定客服岗位:'.$Organization['Position']['name'].PHP_EOL.'客服工号：'.$ChaserviceModel['insertId'];
        }
        return ['content'=>$Organization['Staff']['name'].':您好'.PHP_EOL.'修改客服岗位信息:'.$Organization['Position']['name'],'reply_type'=>'text'];
    }

    /**
     * 邀请关注 门店
     */
    protected function InviteAttentionShop($obj,$Ticket)
    {
        /**
         * 写入关系
         */
        $data = [
            'type'=>'wechat',
            'value'=>$obj->fromUsername,
            'referee'=>'00000000-0000-0000-0000-000000000000',//推荐人
            'ascription'=>$Ticket['content'],//推荐门店
        ];
        ThirdRecommendBind::open()->add($data);

        $Keyword = KeywordModel::open()->equals('name','qrInviteAttentionShop')->first();
        if(empty($Keyword)){return'';}
        return ['content'=>$Keyword['content'],'reply_type'=>$Keyword['type']];
    }
    /**
     * 邀请关注 员工
     */
    protected function InviteAttentionStaff($obj,$Ticket)
    {

        /**
         * 写入关系
         */
        $data = [
            'type'=>'wechat',
            'value'=>$obj->fromUsername,
            'referee'=>$Ticket['content'],//推荐人
            'ascription'=>$Ticket['storeId'],//推荐门店
        ];
        ThirdRecommendBind::open()->add($data);

        $Keyword = KeywordModel::open()->equals('name','qrInviteAttentionShop')->first();
        if(empty($Keyword)){return'';}
        return ['content'=>$Keyword['content'],'reply_type'=>$Keyword['type']];
    }


}