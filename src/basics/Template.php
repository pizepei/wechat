<?php
/**
 * @Author: pizepei
 * @Date:   2017-06-03 14:39:36
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-06-28 16:35:55
 */

namespace pizepei\wechat\basics;

use jt\error\Exception;
use model\wechat\TemplateModel;
use pizepei\helper\Helper;
use pizepei\model\redis\Redis;
use pizepei\wechat\model\OpenWechatTemplateModel;
use pizepei\wechat\service\Config;
use utils\Logger;
use utils\Sundry;
use utils\wechatbrief\func;
use utils\wx\common\WechatBase;

/**
 * 微信  模板通知
 */
class Template
{

    protected $openid = '';//接受者id

    protected $template_id_short = '';//模板短id

    protected $template_id = ''; //模板长id

    protected $url = '';//url

    protected $access_token = '';//access_token

    protected $data = '';//数据
    protected $authorizer_appid = '';//公众号appid
    /**
     * @var array
     */
    protected $template_data = '';//模板-模型-数据

    protected $template_model = '';//最后的数据

    protected $Add_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';//请求地址

    protected $addTemplateApi = 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='; //获取模板ID

    /**
     * Template constructor.
     * @param string $authorizer_appid  微信公众号appid
     * @param string $openid   [接受者id]
     * @param string $template_id_short [模板id] 短ID
     * @param string $url 详情url地址
     */
    public function __construct(string $authorizer_appid,string $openid,string $template_id_short, string $url = '')
    {
        $config = new Config(Redis::init());
        $this->access_token  = $config->access_token($authorizer_appid)['authorizer_access_token'];
        //初始化  参数
        $this->url               = $url;
        $this->template_id_short = $template_id_short;
        $this->openid            = $openid;
        $this->authorizer_appid  = $authorizer_appid;
    }

    /**
     * 获取模板id
     * https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token=ACCESS_TOKEN
     */
    private function getTempLateId()
    {
        $url  = $this->addTemplateApi.$this->access_token;
        $data = [
            'template_id_short' => $this->template_id_short,
        ];
        Helper::init()->syncLock(Redis::init(),['open','getTempLateId',$this->template_id_short]);#设置syncLock
        $jsonRes = Helper::init()->httpRequest($url, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))['body'];
        var_dump($jsonRes);
        $res = json_decode($jsonRes, true);
        if($res['errcode'] == 0){
            Helper::init()->syncLock(Redis::init(),['open','getTempLateId',$this->template_id_short],false);#设置syncLock
            return $res['template_id'];
        }else{
            Helper::init()->syncLock(Redis::init(),['open','getTempLateId',$this->template_id_short],false);#设置syncLock
            throw new \Exception($res['errmsg']);
        }
    }

    /**
     * 获取模板列表
     * https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=ACCESS_TOKEN
     */
    //public static function get_all_private_template()
    //{
    //    $data = func::send('GET_ALL_PRIVATE_TEMPLATE', null, true);
    //    if(!isset($data['template_list'])){
    //        return null;
    //    }
    //    $data = $data['template_list'];
    //    foreach(self::TEMPLATE as $v){
    //        $e = true;
    //        foreach($data as $vv){
    //            if($v['title'] == $vv['title']){
    //                $e = false;
    //            }
    //        }
    //        if($e){
    //            func::send('GET_ADD_TEMPLATE', ['template_id_short' => $v['templateID']], true);
    //        }
    //    }
    //
    //    return $data;
    //}

    /**
     * 发送模板通知
     * @Effect
     * @param  [type] $data [需要发送的模板数据]注意内容数量必须一致
     * @return [array]  "errcode": 0, errmsg": "ok",msgid": 914761096463106048,
     */
    public function send($data)
    {
        $this->data = $data;
        //通过模板id  获取模板模型
        $this->ModelData();
        //向模板模型中插入需要发送的数据
        $this->model();
        //准备  url
        $Add_url = $this->Add_url.$this->access_token;
        $res = Helper::init()->httpRequest($Add_url, $this->template_model)['body'];
        //返回结果
        return json_decode($res??'', true);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/26 12:39
     * @title  拼接数据
     * @explain
     * @return bool
     */
    public function Model()
    {
        //判断  是否有模板
        if(!$this->template_data){
            return false;
        }
        /**
         * 判断存入数据类型
         */
        $type = false;
        if(isset($this->data[0]['value'])){
            $type = true;
        }
        $i = 0;
        //初始化模板
        foreach($this->template_data['data'] as $key => $value){
            if($type){
                $this->template_data[$key] = $this->data[$i];
            }else{
                $this->template_data['data'][$key]['value'] = $this->data[$i];
            }
            ++$i;
        }
        //处理数据
        $template_model       = [
            'touser'      => $this->openid,
            'template_id' => $this->template_id,
            'url'         => $this->url,
            'data'        => $this->template_data['data'],
        ];
        $this->template_model = json_encode($template_model);

        return true;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/26 17:04
     * @title  模板数据模型 负责获取模板id缓存数据库
     * @explain 模板数据模型
     * @throws \Exception
     */
    public function ModelData()
    {
        $this->template_data = BasicsConst::TEMPLATE[$this->template_id_short];//获取模板
        # 考虑是否加缓存（如果有就缓存）
        $tempModel = OpenWechatTemplateModel::table();
        $tempData= $tempModel
            ->where(['template_id_short'=>$this->template_id_short,'authorizer_appid'=>$this->authorizer_appid])
            ->cache(['template_id_short',$this->template_id_short],15)#缓存
            ->fetch(['template_id']);

        if(Helper::init()->is_empty($tempData['template_id'])){
            $this->template_id = $this->getTempLateId();
            $tempModel->add(
                [
                'authorizer_appid' => $this->authorizer_appid ,
                'template_id_short' => $this->template_id_short,//模板库中模板的编号，有“TM**”和“OPENTMTM**”等形式
                'template_id'       => $this->template_id,//模板唯一id
                'title'             => $this->template_data['title']??'',//
                'primary_industry'  => $this->template_data['primary_industry']??'',//
                'deputy_industry'   => $this->template_data['deputy_industry']??'',//
            ]
            );
        }else{
            $this->template_id = $tempData['template_id'];
        }

    }


}

