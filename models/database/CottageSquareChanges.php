<?php


namespace app\models\database;


use app\models\interfaces\CottageInterface;
use app\models\Table_cottages;
use app\models\TimeHandler;
use Exception;
use yii\db\ActiveRecord;

/**
 * Class CottageSquareChanges
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage_number [varchar(10)]
 * @property int $old_square [int(10) unsigned]
 * @property int $new_square [int(10) unsigned]
 * @property int $date [bigint(20) unsigned]
 */

class CottageSquareChanges extends ActiveRecord
{
    public static function tableName():string
    {
        return 'cottage_square_changes';
    }

    /**
     * @param CottageInterface $cottage
     * @param string $targetQuarter
     * @return mixed
     * @throws Exception
     */
    public static function getQuarterSquare(CottageInterface $cottage, string $targetQuarter)
    {
        // Проверю, не менялась ли площадь участка
        $cottageChanges = self::find()->where(['cottage_number' => $cottage->getCottageNumber()])->orderBy('date DESC')->all();
        if($cottageChanges === null){
            return $cottage->cottageSquare;
        }
        $quarter = null;
        $cottageChange = null;
        foreach ($cottageChanges as $cottageChange) {
            $quarter = TimeHandler::getQuarterFromTimestamp($cottageChange->date);
            // если целевой квартал раньше изменения- верну площадь участка
            if($targetQuarter < $quarter){
                return $cottageChange->old_square;
            }
            if($targetQuarter === $quarter){
                return $cottageChange->new_square;
            }
        }
        if($quarter !== null && $cottageChange !== null){

            // проверю, если целевой квартал всё ещё меньше последнего изменения- верну старую площадь
            if($targetQuarter < $quarter){
                return $cottageChange->old_square;
            }
                return $cottageChange->new_square;
        }
        return $cottage->cottageSquare;
    }

    /**
     * Обработаю изменение площади
     * @param CottageInterface $cottage
     * @param $newSquare
     */
    public static function changeSquare(CottageInterface $cottage, $newSquare): void
    {
        (new self([
            'cottage_number' => $cottage->getCottageNumber(),
            'old_square' => $cottage->getSquare(),
            'new_square' => $newSquare,
            'date' => time()
        ]))->save();
        $cottage->cottageSquare = $newSquare;
        $cottage->save();
        // сменю значение во всех начислениях, начиная с текущего квартала
        $pastAccruals = Accruals_membership::find()->where(['>=', 'quarter', TimeHandler::getCurrentQuarter()])->andWhere(['cottage_number' => $cottage->getCottageNumber()])->all();
        if(!empty($pastAccruals)){
            foreach ($pastAccruals as $pastAccrual) {
                $pastAccrual->counted_square = $newSquare;
                $pastAccrual->save();
            }
        }
    }
}