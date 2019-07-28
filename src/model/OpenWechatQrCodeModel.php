<?php
/**
 * 微信带参数的二维码的数据
 */

namespace pizepei\wechat\model;


use pizepei\model\db\Model;

class OpenWechatQrCodeModel extends Model
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
        'expire_seconds'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>60, 'COMMENT'=>'有效期0为永久单位s',
        ],
        'scene_id'=>[
            'TYPE'=>'varchar(65)', 'DEFAULT'=>false, 'COMMENT'=>'自定义二维码参数',
        ],
        'ticket'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>false, 'COMMENT'=>'微信二维码标识',
        ],
        'content'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>false, 'COMMENT'=>'文字内容',
        ],
        'url'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'二维码的内容可用来生成二维码',
        ],
        'terrace'=>[
            'TYPE'=>"ENUM('1','2','3','4')", 'DEFAULT'=>'4', 'COMMENT'=>'平台1、用户2、pc后台3、第三方4、更多',
        ],
        'type'=>[
            'TYPE'=>"varchar(50)", 'DEFAULT'=>'', 'COMMENT'=>'操作类型对应BasicsConst::QrcodeType',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展比如用户信息',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1、未使用2、已使用3、其他4、异常',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'authorizer_appid,scene_id,ticket','NAME'=>'authorizer_appid,scene_id,ticket','USING'=>'BTREE','COMMENT'=>'微信appid,自定义的参数,微信二维码标识'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信带参数二维码记录表';
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