<?php
/**
 * @title 验证应用表
 */

namespace pizepei\wechat\model;


use pizepei\model\db\Model;

class OpenWechatCodeAppModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'account表id',
        ],
        'name'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'应用名称',
        ],
        'remark'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'app备注说明',
        ],
        'domain'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'域名',
        ],
        'target_url'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'授权成功回调url地址（域名+参数）',
        ],
        'encoding_aes_key'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'', 'COMMENT'=>'加密参数',
        ],
        'token'=>[
            'TYPE'=>'varchar(50)', 'DEFAULT'=>'', 'COMMENT'=>'验证签名token',
        ],
        'app_secret'=>[
            'TYPE'=>'varchar(32)', 'DEFAULT'=>'', 'COMMENT'=>'签名secret',
        ],
        'ip_white_list'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'ip白名单',
        ],
        'authorizer_appid'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'公众号ppid',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3')", 'DEFAULT'=>'1', 'COMMENT'=>'1默认等待审核2审核成功3禁止使用',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[

        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键

    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信验证app表';
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