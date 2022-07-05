<?php


namespace app\models\database;


use app\models\interfaces\CottageInterface;
use Exception;
use Throwable;
use Yii;
use yii\db\ActiveRecord;

/**
 * Class Mail
 *
 * @package app\models
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage [varchar(20)]
 * @property string $phonesData
 * @property string $state [enum('not_called', 'not_available', 'will_come')]
 */
class Calling extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'calling';
    }

}