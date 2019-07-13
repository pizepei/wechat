<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/13 15:53
 * @title 微信关键字表
 */

namespace pizepei\wechat\model;


use pizepei\model\db\Model;

class WechatKeywordModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'authorizer_appid'=>[
            'TYPE'=>'varchar(42)', 'DEFAULT'=>'', 'COMMENT'=>'公众号appid',
        ],
        'component_appid'=>[
            'TYPE'=>'varchar(42)', 'DEFAULT'=>'', 'COMMENT'=>'第三方平台appid',
        ],
        'title'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'关键字规则名称',
        ],
        'name'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'关键字',
        ],
        'match_type'=>[
            'TYPE'=>"ENUM('10','20')", 'DEFAULT'=>'10', 'COMMENT'=>'10全匹配,20模糊匹配',
        ],
        'model'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'模型名称（模块）',
        ],
        'method'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'模型方法名称',
        ],
        'type'=>[
            'TYPE'=>"ENUM('text','image','news','video','event')", 'DEFAULT'=>'text', 'COMMENT'=>'回复类型',
        ],
        'status'=>[
            'TYPE'=>"ENUM('10','20')", 'DEFAULT'=>'20', 'COMMENT'=>'是否生效 10生效 20不生效',
        ],
        'content'=>[
            'TYPE'=>'text', 'DEFAULT'=>false, 'COMMENT'=>'回复内容',
        ],
        'operation'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'操作信息',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'name,authorizer_appid','NAME'=>'name,authorizer_appid','USING'=>'BTREE','COMMENT'=>'关键字'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信关键字表';
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