<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:55
 */

namespace app\models;


use app\models\interfaces\CottageInterface;
use yii\db\ActiveRecord;

/**
 * Class Table_payed_membership
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property string $quarter [varchar(10)]
 * @property float $summ [float unsigned]
 * @property int $paymentDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_payed_membership extends ActiveRecord
{
    public static function tableName() :string
    {
        return 'payed_membership';
    }

    public static function getPays(CottageInterface $cottage, string $period){
        if($cottage->isMain()){
            return self::findAll(['quarter' => $period, 'cottageId' => $cottage->getCottageNumber()]);
        }
        return Table_additional_payed_membership::findAll(['quarter' => $period, 'cottageId' => $cottage->getCottageNumber()]);

    }

    /**
     * @param int $id
     * @return Table_payed_membership[]
     */
    public static function getPayedInTransaction(int $id): array
    {
        return self::findAll(['transactionId' => $id]);
    }
}