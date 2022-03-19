<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 *
 * @property int $id [int(10) unsigned]
 * @property string $person_id [varchar(255)]
 * @property int $log_level [int(11)]
 */
class TelegramHandler extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'telegram_bindings';
    }

    public static function contains(int|string $getId): bool
    {
        return self::find()->where(['person_id' => $getId])->count() === 1;
    }

    public static function register(int|string $getId): void
    {
        if (self::find()->where(['person_id' => $getId])->count() === 0) {
            (new self(['person_id' => $getId, 'log_level' => 1]))->save();
        }
    }
}