<?php
/**
 * @Author: pizepei
 * @Date:   2017-07-12 14:39:36
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-06-28 16:34:13
 * @title 开放平台配置
 */
namespace pizepei\wechat\model;

use pizepei\model\db\Model;

class OpenWechatConfigModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'name'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'配置名字',
        ],
        'remark'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'备注',
        ],

        'appid'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'appid',
        ],
        'appsecret'=>[
            'TYPE'=>'varchar(32)', 'DEFAULT'=>22, 'COMMENT'=>'appsecret',
        ],
        'EncodingAESKey'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'', 'COMMENT'=>'消息加密密钥由43位字符组成，可随机修改，字符范围为A-Z，a-z，0-9',
        ],
        'token'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'bt', 'COMMENT'=>'消息校验Token',
        ],
        'open_domain'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'开发域名、小程序、公众号',
        ],
        'extend'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'cache_time'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>120, 'COMMENT'=>'缓存时间单位s',
        ],
        'cache_prefix'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'wechat:open:', 'COMMENT'=>'获取prefix',
        ],

        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1、停用2、启用3、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'appid','NAME'=>'appid','USING'=>'BTREE','COMMENT'=>'平台appid'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信第三方平台配置表';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [
    ];
}