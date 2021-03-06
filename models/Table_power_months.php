<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 23:04
 */

namespace app\models;

use app\models\interfaces\CottageInterface;
use yii\db\ActiveRecord;

/**
 * Class Table_power_months
 * @package app\models
 * @property int $id [int(6) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property string $month [varchar(20)]
 * @property int $fillingDate [int(20) unsigned]
 * @property int $oldPowerData [int(10) unsigned]
 * @property int $newPowerData [int(10) unsigned]
 * @property int $searchTimestamp [int(20) unsigned]
 * @property string $payed [enum('yes', 'no')]
 * @property int $difference [int(10) unsigned]
 * @property float $totalPay [float unsigned]
 * @property int $inLimitSumm [int(10) unsigned]
 * @property int $overLimitSumm [int(10) unsigned]
 * @property float $inLimitPay [float unsigned]
 * @property float $overLimitPay [float unsigned]
 */

class Table_power_months extends ActiveRecord
{
    public static function tableName():string
    {
        return 'months_power';
    }

    /**
     * @param CottageInterface $cottage
     * @return Table_power_months|Table_additional_power_months
     */
    public static function getLastFilled(CottageInterface $cottage)
    {
        if($cottage->isMain()){
            return self::find()->where(['cottageNumber' => $cottage->getCottageNumber()])->orderBy('month DESC')->one();
        }
        return Table_additional_power_months::find()->where(['cottageNumber' => $cottage->getCottageNumber()])->orderBy('month DESC')->one();

    }

    /**
     * @param CottageInterface $cottageInfo
     * @return Table_power_months[]
     */
    public static function getAllData(CottageInterface $cottageInfo): array
    {
        return self::findAll(['cottageNumber' => $cottageInfo->cottageNumber]);
    }

    public static function getFirstUnpaid(CottageInterface $cottage)
    {
        return self::find()->where(['cottageNumber' => $cottage->getCottageNumber(), 'payed' => 'no'])->andWhere(['<>', 'difference', 0])->orderBy('month')->one();
    }

    public static function getData(CottageInterface $cottageInfo, $date)
    {
        if($cottageInfo->isMain()){
            return self::findOne(['cottageNumber' => $cottageInfo->getCottageNumber(), 'month' => $date]);
        }
        return Table_additional_power_months::findOne(['cottageNumber' => $cottageInfo->getBaseCottageNumber(), 'month' => $date]);
    }

    /**
     * ?????????? ?????? ???????????????????? ???? ??????????????
     * @param Table_cottages $cottageInfo
     * @return Table_power_months[]
     */
    public static function getCottageAccruals(Table_cottages $cottageInfo): array
    {
        if($cottageInfo->isMain()){
            return self::findAll(['cottageNumber' => $cottageInfo->cottageNumber]);
        }
        return Table_additional_power_months::findAll(['cottageNumber' => $cottageInfo->cottageNumber]);
    }

    /**
     * ????????????????, ?????????????? ???? ??????????
     * @return bool
     * @throws ExceptionWithStatus
     */
    public function isFullPayed(): bool
    {
        if($this->totalPay > 0){
            $pays = Table_payed_power::getPayed($this);
            if(!empty($pays)){
                $payedSum = 0;
                foreach ($pays as $pay) {
                    $payedSum = CashHandler::toRubles($payedSum + $pay->summ);
                }
                if($payedSum < $this->totalPay){
                    return false;
                }
                // ?? ?????? ???????? ???????????????????????????????? ???????????? ???? ?????????????? ??????????, ?????? ??????????????????- ?????? ??????????????????????????, ???????????? ???? ????????
                if($payedSum > $this->totalPay){
                    throw new ExceptionWithStatus("???????????? ???? {$this->month} ???? ?????????????? {$this->cottageNumber} : {$payedSum} ????????????, ?????? ?????????????????? ???? ??????????: {$this->totalPay}");
                }
            }
            else{
                return false;
            }
        }
            return true;
    }
}