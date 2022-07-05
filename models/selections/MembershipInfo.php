<?php


namespace app\models\selections;


use yii\base\Model;

class MembershipInfo extends Model
{
    public int $quarter;
    public float $amount = 0;
    public float $payed = 0;
    public string $cottageNumber;
}