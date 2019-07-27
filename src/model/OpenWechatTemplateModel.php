<?php
/**
 * 微信模板通知
 */

namespace pizepei\wechat\model;


use pizepei\model\db\Model;

class OpenWechatTemplateModel extends Model
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
            'TYPE'=>'varchar(45)', 'DEFAULT'=>'', 'COMMENT'=>'微信appid',
        ],
        'component_appid'=>[
            'TYPE'=>'varchar(45)', 'DEFAULT'=>'', 'COMMENT'=>'授权平台appid',
        ],
        'template_id_short'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'模板库中模板的编号，有TM**和OPENTMTM**等形式',
        ],
        'template_id'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'通过接口获取的模板唯一id',
        ],
        'title'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'模板名称',
        ],
        'primary_industry'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'一级行业',
        ],
        'deputy_industry'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'二级行业',
        ],
        'content'=>[
            'TYPE'=>'varchar(800)', 'DEFAULT'=>'', 'COMMENT'=>'内容',
        ],
        'example'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'扩展字段',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'template_id_short,authorizer_appid','NAME'=>'template_id_short,authorizer_appid','USING'=>'BTREE','COMMENT'=>'模板库中模板的编号'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss '
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信模板通知表';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [

    ];
    /**
     * 初始化数据：表不存在时自动创建表然后自动插入$initData数据 支持多条
     * @var array
     */
    protected $initData = [
    ];

}