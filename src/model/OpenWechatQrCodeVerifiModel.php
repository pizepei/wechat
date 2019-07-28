<?php
/**
 * 微信带参数的二维码的验证
 */

namespace pizepei\wechat\model;


use pizepei\model\db\Model;

class OpenWechatQrCodeVerifiModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'qr_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'OpenWechatQrCodeModel的uuid',
        ],
        'authorizer_appid'=>[
            'TYPE'=>'varchar(45)', 'DEFAULT'=>'', 'COMMENT'=>'微信appid',
        ],
        'component_appid'=>[
            'TYPE'=>'varchar(45)', 'DEFAULT'=>'', 'COMMENT'=>'授权平台appid',
        ],
        'appid'=>[
            'TYPE'=>"varchar(45)", 'DEFAULT'=>'4', 'COMMENT'=>'验证码应用appid',
        ],
        'number'=>[
            'TYPE'=>"varchar(12)", 'DEFAULT'=>'', 'COMMENT'=>'手机号码',
        ],
        'email'=>[
            'TYPE'=>"varchar(120)", 'DEFAULT'=>'', 'COMMENT'=>'邮箱',
        ],
        'frequency'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>60, 'COMMENT'=>'获取批量单位s',
        ],
        'scene_id'=>[
            'TYPE'=>'varchar(65)', 'DEFAULT'=>false, 'COMMENT'=>'自定义二维码参数',
        ],
        'ticket'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>false, 'COMMENT'=>'微信二维码标识',
        ],
        'content'=>[
            'TYPE'=>'varchar(10)', 'DEFAULT'=>'', 'COMMENT'=>'验证码内容',
        ],
        'reply_content'=>[
            'TYPE'=>'varchar(1000)', 'DEFAULT'=>'', 'COMMENT'=>'回复文字内容',
        ],
        'reply_type'=>[
            'TYPE'=>"ENUM('text','image','news','video','event')", 'DEFAULT'=>'text', 'COMMENT'=>'微信回复类型',
        ],
        'terrace'=>[
            'TYPE'=>"ENUM('1','2','3','4')", 'DEFAULT'=>'4', 'COMMENT'=>'平台1、用户2、pc后台3、第三方4、更多',
        ],
        'type'=>[
            'TYPE'=>"varchar(50)", 'DEFAULT'=>'', 'COMMENT'=>'操作类型对应BasicsConst::QrcodeType',
        ],
        'pattern'=>[
            'TYPE'=>"varchar(50)", 'DEFAULT'=>'', 'COMMENT'=>'处理模式',
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
            ['TYPE'=>'UNIQUE','FIELD'=>'qr_id','NAME'=>'qr_id','USING'=>'BTREE','COMMENT'=>'OpenWechatQrCodeModel的uuid'],

            ['TYPE'=>'INDEX','FIELD'=>'authorizer_appid','NAME'=>'authorizer_appid','USING'=>'BTREE','COMMENT'=>'微信appid'],
            ['TYPE'=>'INDEX','FIELD'=>'scene_id','NAME'=>'scene_id','USING'=>'BTREE','COMMENT'=>'自定义的参数'],
            ['TYPE'=>'INDEX','FIELD'=>'ticket','NAME'=>'ticket','USING'=>'BTREE','COMMENT'=>'微信二维码标识'],
            ['TYPE'=>'INDEX','FIELD'=>'appid','NAME'=>'appid','USING'=>'BTREE','COMMENT'=>'验证码应用appid'],
            ['TYPE'=>'INDEX','FIELD'=>'number','NAME'=>'number','USING'=>'BTREE','COMMENT'=>'手机号码'],
            ['TYPE'=>'INDEX','FIELD'=>'email','NAME'=>'email','USING'=>'BTREE','COMMENT'=>'邮箱'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微信带参数二维码code验证表';
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