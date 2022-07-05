<?php


namespace app\models\selections;


use yii\base\Model;

class PowerInfo extends Model
{
    public int $month;
    public float $amount = 0;
    public float $payed = 0;
    public string $cottageNumber;
}