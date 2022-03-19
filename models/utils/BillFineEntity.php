<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\GrammarHandler;
use app\models\Table_additional_payed_power;
use app\models\Table_payed_power;
use app\models\Table_payment_bills;
use app\models\Table_transactions;
use app\models\tables\Table_payed_fines;

class BillFineEntity extends BillContentEntity
{

    public bool $payed;
    public float $payedBefore;
    public float $leftToPay;
    public int $fine_id;

    public function getTextContent(): string
    {
        // найду стоимость периода
        $payedInBillCount = $this->getPayedInside();
        $payedOutBillCount = $this->getPayedOutside();
        $leftToPay = CashHandler::toRubles(CashHandler::toRubles($this->totalAccrued) - $payedInBillCount - $payedOutBillCount);
        return "<tr><td>Пени" . "</td><td>{$this->date}</td><td>" . CashHandler::toRubles($this->sum) . "</td><td>" . CashHandler::toRubles($this->sum) . "</td><td>" . CashHandler::toRubles($payedInBillCount) . "</td><td>" . CashHandler::toRubles($payedOutBillCount) . "</td><td>$leftToPay</td></tr>";
    }

    public function getPayedOutside(): float
    {
        $sum = 0;
            $items = Table_payed_fines::find()->where(['fine_id' => $this->fine_id])->all();
        if($items !== null){
            foreach ($items as $item) {
                $transaction = Table_transactions::findOne($item->transaction_id);
                $bill = Table_payment_bills::findOne($transaction->billId);
                if($bill->id !== $this->billId){
                    $sum += $item->summ;
                }
            }
        }
        return $sum;
    }

    public function getPayedInside(): float
    {
        $sum = 0;
        $items = Table_payed_fines::findAll(['fine_id' => $this->fine_id]);
        if($items !== null){
            foreach ($items as $item) {
                $transaction = Table_transactions::findOne($item->transaction_id);
                $bill = Table_payment_bills::findOne($transaction->billId);
                if($bill->id === $this->billId){
                    $sum += $item->summ;
                }
            }
        }
        return $sum;
    }

    public function getLeftToPay():float
    {
        return $this->totalAccrued - ($this->getPayedOutside() + $this->getPayedInside());
    }
}