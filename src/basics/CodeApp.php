<?php


namespace pizepei\wechat\basics;


use pizepei\encryption\aes\Prpcrypt;
use pizepei\encryption\SHA1;
use pizepei\helper\Helper;
use pizepei\model\redis\Redis;
use pizepei\service\websocket\Client;
use pizepei\wechat\model\OpenWechatCodeAppLog;
use pizepei\wechat\model\OpenWechatCodeAppModel;
use pizepei\wechat\service\Config;
use pizepei\wechat\service\Open;

class CodeApp
{
    /**
     * @Author 皮泽培
     * @Created 2019/8/10 14:00
     * @param array $path
     * @param array $get
     * @return array [json] 定义输出返回数据
     * @title  初步验证app  url
     * @explain 路由功能说明
     * @return array
     * @throws \Exception
     */
    public function initialUrlVerifyHtml(array $path,array$get):array
    {
        $res = $this->timestampVerify($path,$get,true);
        if ($res['result'] == 'no'){return $res;}
        # 准备前端需要的数据
        $data= [
                'title'=>'微信验证',
                'appName'=>$res['appData']['name'],
                'icon'=>$res['appData']['icon']??'http://wx.qlogo.cn/mmopen/8489CVblktE9R0ffybtAmdY7qmWQyr2P16CbMxGjmuX9eydtPI64X2mG9QK1ghTg5t0vcop3fRaxeVH5bAoqv2wge61UR0na/0',
                'hint'=>'是否允许微信进行验证操作?',
                'risk'=>'本操作不获取个人信息,只验证在['.$res['appData']['name'].']上的操作为您本人操作！',
                'result'=>'ok'
        ];
        return $data;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/8/10 14:00
     * @param array $path
     * @param array $get
     * @return array
     * @title  签名验证
     * @return array
     * @throws \Exception
     */
    public function timestampVerify(array $path,array$get,bool $ticketSignature=false):array
    {
        # 1 验证signature确认appid下的qrLogId 合法
        # 2 通过appid和qrLogId获取二维码信息
        # 3 合法输出确认授权html 不合法输出错误提示 （使用参数绑定直接转入是否合法信息）
        $App = OpenWechatCodeAppModel::table()
            ->where(['id'=>$path['appid'],'status'=>2])
            ->cache(['OpenWechatCodeApp','config'],60)
            ->fetch();
        if (empty($App)){return ['result'=>'no','msg'=>'应用不存在'];}
        # 验证有效期
//        if ($get['period'] < time()){return ['result'=>'no','msg'=>'验证超时请重新获取二维码'];}
        # 验证signature确认appid下的qrLogId 合法   应用appid+时间戳timestamp+nonce随机数+app_secret+$CodeAppLog[id]+period
        if (md5($path['appid'].$get['timestamp'].$get['nonce'].$App['app_secret'].$path['id'].$get['period'].$get['openid']) !== $get['signature']){
            return ['result'=>'no','msg'=>'签名错误'];
        }
        # 验证$ticketSignature
        if ($ticketSignature){
            # 查询日志
            $AppLog = OpenWechatCodeAppLog::table($get['authorizer_appid'])
                ->where(['appid'=>$path['appid'],'id'=>$path['id']])
                ->fetch();
            if (empty($AppLog)){return ['result'=>'no','msg'=>'不存在的验证'];}
            if($AppLog['status'] != 1){
                $status = [2=>'已使用',3=>'其他',4=>'异常'];
                return ['result'=>'no','msg'=>$status[$AppLog['status']]];
            }
            # 验证签名
            if (md5($path['appid'].$get['timestamp'].$get['ticketNonce'].$AppLog['qr_id'].$AppLog['scene_id'].$get['openid'].$get['authorizer_appid']) !== $get['ticketSignature']){
                return ['result'=>'no','msg'=>'签名错误1'];
            }
        }
        return ['result'=>'yes','msg'=>'验证成功','appData'=>$App,'appLog'=>$AppLog??[]];
    }
    /**
     * @Author 皮泽培
     * @Created 2019/8/10 14:00
     * @param array $path
     * @param array $get
     * @return array [json] 定义输出返回数据
     * @title  确认授权验证结果
     * @return array
     * @throws \Exception
     */
    public function urlVerifyHtmlConfirm(array $path,array$get):array
    {
        $result = $this->timestampVerify($path,$get,true);
        if ($result['result'] == 'on'){
            return $result;
        }
        # 获取微信信息
        $config = new Config(Redis::init());
        $OpenConfig = $config->getOpenConfig(false);
        Open::init($OpenConfig,Redis::init());
        if ($get['authorizer_appid'] !== $get['appid']){
            throw new \Exception('错误的公众号信息');
        }
        $AccessToken = Open::oauth2AccessToken($get['code'],$get['appid']);
        if ($AccessToken['openid'] !== $get['openid'])
        {
            throw new \Exception('请使用扫描二维码的微信进行操作');
        }
        #确认授权
        if ($get['event'] == 10)
        {
            $behavior = 'accept';
            $msg = '验证成功';
        }elseif($get['event'] == 20)# 拒绝授权
        {
            $behavior = 'refuse';
            $msg = '已拒绝';
        }
        # 修改二维码状态
        $Confirm = [
                'openid'=>$get['openid'],
                'date'=>date('Y-m-d H:i:s'),
                'behavior'=>$behavior,
        ];
        $AppLog = OpenWechatCodeAppLog::table($get['authorizer_appid'])
            ->where(['appid'=>$path['appid'],'id'=>$path['id'],'status'=>1])
            ->update(['extend'=>['Confirm'=>$Confirm],'status'=>2]);
        # 通知客户端
        # 推送 WebSocket
        # jwt 规则
        $Client = new Client([
            'data'=>[
                'uid'=>Helper::init()->getUuid(),
                'app'=>'codeApp',
            ],
        ]);

        # 获取粉丝信息
        $fansUserIfon = Open::fansUserIfon($get['openid'],$get['appid'],false);

        $Client->connect();
        #判断是否在线
        $ClientInfo = $Client->exist($path['id']);
        if (!$ClientInfo){
            return ['result'=>'no','msg'=>'请不要关闭电脑页面'];
        }
        $contentData = [
            'id'=>$path['id'],
            'code'=>$result['appLog']['code'],
            'type'=>$result['appLog']['type'],
            'pattern'=>$result['appLog']['pattern'],
            'openid'=>$get['openid'],
            'behavior'=>$behavior,
            'remote_ip' =>$ClientInfo['remote_ip'],
            'fansUserIfon'=>$fansUserIfon,
            'content'=>$result['appLog']['content'],
        ];
        # 加密数据
        $Prpcrypt = new Prpcrypt($result['appData']['encoding_aes_key']);
        $encrypted = $Prpcrypt->encrypt(json_encode($contentData),$path['appid']);
        if (empty($encrypted)){
            return ['result'=>'no','msg'=>'加密错误'];
        }
        # 签名
        $SHA1 = new SHA1();
        $encrypted = $SHA1->setSignature($result['appData']['token'],$encrypted);
        if (empty($encrypted)){
            return ['result'=>'no','msg'=>'加密签名错误'];
        }
        $ClientData = [
            'type'=>'init',
            'content'=>'授权事件',
            'appid'=>$path['appid'],//code 应用的id
            'contentData'=>$contentData,//提供给
            'encrypted'=>$encrypted,//加密的信息包括微信粉丝信息
            'confirm'=>$Confirm,//授权结果
        ];

        $res = $Client->sendUser($path['id'],$ClientData,true);
        if ($res){
            return ['result'=>'ok','msg'=>$msg];
        }
        return ['result'=>'no','msg'=>'操作失败'];
    }
    /**
     * @Author 皮泽培
     * @Created 2019/8/10 14:00
     * @param array $path
     * @param array $get
     * @return array [json] 定义输出返回数据
     * @title  获取OAuth20 url
     * @return array
     * @throws \Exception
     */
    public function getUrlVerifyOAuth20(array $path,array$get)
    {
        $result = $this->timestampVerify($path,$get,true);
        if ($result['result'] == 'on'){
            return $result;
        }
        # 安全起见 使用微信网页授权（订阅号只能是用来验证验证码不能使用这个流程）
        $config = new Config(Redis::init());
        $OpenConfig = $config->getOpenConfig(false);
        Open::init($OpenConfig,Redis::init());
        $url = (Helper::init()->is_https()?'https://':'http://').$_SERVER['HTTP_HOST'].'/'.\Deploy::MODULE_PREFIX.'/wechat/common/code-app/verify/'.$path['appid'].'/'.$path['id'].'.html?'.http_build_query($get);
        return Open::OAuth($get['authorizer_appid'],urlencode($url));
    }

}