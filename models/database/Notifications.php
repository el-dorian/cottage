<?php /** @noinspection UndetectableTableInspection */


namespace app\models\database;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * @property int $id [int(10) unsigned]
 * @property int $cottage_id [int(10) unsigned]
 * @property string $title [varchar(100)]
 * @property string $message
 * @property string $type [varchar(20)]
 * @property bool $is_read [tinyint(1)]
 * @property int $time_of_creation [bigint(20)]
 * @property string $broadcast_id [varchar(255)]
 */
class Notifications extends ActiveRecord


{
    public static function tableName(): string
    {
        return 'notifications';
    }

    public static function getDb(): Connection
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return Yii::$app->db1;
    }
}