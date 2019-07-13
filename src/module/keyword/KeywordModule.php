<?php
/**
 * @Author: anchen
 * @Date:   2017-04-18 22:22:41
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-06-28 17:02:10
 */
namespace pizepei\wechat\module\keyword;
use model\tenant\OpenAuthorizerUserInfoModel;
use pizepei\wechat\module\BaseModule;
use service\BasicsPort\Config;

class KeywordModule extends BaseModule
{
    /**
     * @Author 皮泽培
     * @Created 2019/7/13 17:37
     * @title  基础关键字回复
     * @explain 关键字回复
     * @throws \Exception
     */
    function index()
    {
        switch($this->obj->reply_type)
        {
            case 'text'://文字回复

             $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, $this->obj->reply_content);
            // file_put_contents('content_text.txt',$content_text);
            return $content_text;
                break;

            case 'news'://图文回复
                //于对 JSON 格式的字符串进行解码，并转换为 PHP 变量。  true 当该参数为 TRUE 时，将返回数组，FALSE 时返回对象。
                $content = json_decode($this->obj->reply_content, true);
            // $content = array(

            //     array('Title'=>'会员中心','Description'=>'速度是多少','PicUrl'=>'http://abcabc1314.net/sc/wxbt.jpg','Url'=>'http://www.pizepei.com')

            //     );
                //获取图文数量
                $count = count($content);
                //这里要先替换,链接里面很可能有%出现，导致sprintf替换失败
                $news = sprintf($this->obj->template_Type,$this->obj->fromUsername,$this->obj->toUsername,$this->obj->time,$this->obj->reply_type,$count);
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

                $content_text = str_replace('{$item}',$value,$news);

                return $content_text;
/*
json 格式
    $content = array(

        array('Title'=>'会员中心','Description'=>'云海翻腾','PicUrl'=>'http://wx1.sinaimg.cn/mw690/006D2KSdly1fghgdujwsnj31jk111n1y.jpg','Url'=>'http://fuliba.net'),
        array('Title'=>'会员中心','Description'=>'云海翻腾','PicUrl'=>'http://wx1.sinaimg.cn/mw690/006D2KSdly1fghgdujwsnj31jk111n1y.jpg','Url'=>'http://fuliba.net'),
        array('Title'=>'会员中心','Description'=>'云海翻腾','PicUrl'=>'http://wx1.sinaimg.cn/mw690/006D2KSdly1fghgdujwsnj31jk111n1y.jpg','Url'=>'http://fuliba.net')
        );
    echo $content = json_encode($content);
*/
                break;
            case 'image'://图片

                $content_text = sprintf($this->obj->template_Type,$this->obj->fromUsername,$this->obj->toUsername,$this->obj->time,$this->obj->reply_type,$this->obj->reply_content);
                // file_put_contents('content_textnews.txt',$content_text);
                return $content_text;

                break;
            case 'voice'://语音

                $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, $this->obj->reply_content);
                //file_put_contents('content_text.txt',$content_text);
                return $content_text;

                break;

            case 'video'://视频

                break;


            case 'event' ://事件

                break;

            default:
        }
    }
    /**
     * 获取openid
     * @return string
     */
    public function getOpenid()
    {
        $content_text = sprintf($this->obj->template_Type, $this->obj->fromUsername, $this->obj->toUsername, $this->obj->time, $this->obj->reply_type, $this->obj->fromUsername);
        return $content_text;
    }

}
