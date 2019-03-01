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

    /**
     * @var array
     */
    protected $template_data = '';//模板-模型-数据

    protected $template_model = '';//最后的数据

    protected $Add_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';//请求地址

    protected $addTemplateApi = 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token='; //获取模板ID

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

    ];

    /**
     * [__construct 构造函数，获取Access Token]
     *
     * @Effect
     * @param  [type] $openid      [接受者id]
     * @param  [type] $template_id_short [模板id] 短ID
     * @param  string $url [url]
     */
    public function __construct($openid, $template_id_short, $url = '')
    {      //获取AccessToken
        //$AccessToken        = new AccessToken();
        //$this->access_token = $AccessToken->access_token();
        $wxBase             = new WechatBase();
        $this->access_token = $wxBase->getAccessToken();
        //初始化  参数
        $this->url               = $url;
        $this->template_id_short = $template_id_short;
        $this->openid            = $openid;
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

        $jsonRes = func::http_request($url, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $res = json_decode($jsonRes, true);

        if($res['errcode'] == 0){
            return $res['template_id'];
        }else{
            Logger::logToTable('获取模板失败_'.date('Y-m-d').'_'.Sundry::randomStr(5),$res,true);
            return '';
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
     * [send 发送模板]
     *
     * @Effect
     * @param  [type] $data [需要发送的模板数据]
     * @return [type]       [description]
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
        //        var_dump($this->template_model);
        //        exit;
        //curl  请求
        $res = func::http_request($Add_url, $this->template_model);

        //返回结果
        return json_decode($res, true);
    }

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

    //模板数据模型
    public function ModelData()
    {

        $this->template_data = self::TEMPLATE[$this->template_id_short];//获取模板

        $tempModel = TemplateModel::open();
        $template_id = $tempModel->equals('template_id_short', $this->template_id_short)->value('template_id');

        if(empty($template_id)){
            $this->template_id = $this->getTempLateId();

            $tempModel->add([
                'template_id_short' => $this->template_id_short,//模板库中模板的编号，有“TM**”和“OPENTMTM**”等形式
                'template_id'       => $this->template_id,//模板唯一id
                'title'             => $this->template_data['title']??'',//
                'primary_industry'  => $this->template_data['primary_industry']??'',//
                'deputy_industry'   => $this->template_data['deputy_industry']??'',//
            ]);
        }else{
            $this->template_id = $template_id;
        }

    }


}

