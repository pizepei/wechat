<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/3/2 15:53
 * @title 开放平台被授权的公众号推送信息日志
 */

namespace pizepei\wechat\model;

use pizepei\model\db\Model;

class OpenMessageLogModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'title'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'日志名称',
        ],
        'appid'=>[
            'TYPE'=>'varchar(42)', 'DEFAULT'=>'', 'COMMENT'=>'公众号appid',
        ],
        'input'=>[
            'TYPE'=>'text', 'DEFAULT'=>false, 'COMMENT'=>'input原始数据',
        ],
        'request'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'get请求参数',
        ],
        'msg'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'解密后的数据',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'xmlToArray'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'xml转换Array',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'INDEX','FIELD'=>'appid','NAME'=>'appid','USING'=>'BTREE','COMMENT'=>'公众号appid'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss '
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '开放平台被授权的公众号推送信息日志';
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