<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 11:51
 */

namespace app\widgets;


use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\Table_additional_payed_membership;
use app\models\Table_additional_payed_power;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_single;
use app\models\Table_payed_target;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use app\models\Telegram;
use app\models\TimeHandler;
use yii\base\Widget;


class TransactionsDetailsWidget extends Widget
{
    public array $info;
    public $content = '';


    public function run()
    {
        echo '<table class="table table-condensed"><caption>Транзакции по счёту</caption><tr><th>Идентификатор</th><th>Дата</th><th>Сумма</th><th>С депозита</th><th>На депозит</th></tr>';
        /** @var \app\models\Table_transactions $item */
        foreach ($this->info as $item) {
            echo "";
            echo "
            <tr>
                <td>$item->id</td>
                <td>" . TimeHandler::getDatetimeFromTimestamp($item->transactionDate) . "</td>
                <td>" . CashHandler::toSmoothRubles($item->transactionSumm) . "</td>
                <td>" . CashHandler::toSmoothRubles($item->usedDeposit) . "</td>
                <td>" . CashHandler::toSmoothRubles($item->gainedDeposit) . "</td>
            </tr>
            <tr>
                <td colspan='5'>
                    <table class='table table-condensed table-striped'>
                    <caption>Оплачено в транзакции</caption>";
            $payedPower = Table_payed_power::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $additionalPayedPower = Table_additional_payed_power::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $payedMembership = Table_payed_membership::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $additionalPayedMembership = Table_additional_payed_membership::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $payedTarget = Table_payed_target::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $additionalPayedTarget = Table_additional_payed_target::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $payedSingle = Table_payed_single::findAll(['transactionId' => $item->id, 'cottageId' => $item->cottageNumber]);
            $payedFiles = Table_payed_fines::findAll(['transactionId' => $item->id]);
            if (!empty($payedPower)) {
                foreach ($payedPower as $value) {
                    echo "<tr><td>Электроэнергия</td><td>$value->month</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedPower)) {
                foreach ($additionalPayedPower as $value) {
                    echo "<tr><td>Электроэнергия(доп)</td><td>$value->month</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedMembership)) {
                foreach ($payedMembership as $value) {
                    echo "<tr><td>Членские</td><td>$value->quarter</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedMembership)) {
                foreach ($additionalPayedMembership as $value) {
                    echo "<tr><td>Членские(доп)</td><td>$value->quarter</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedTarget)) {
                foreach ($payedTarget as $value) {
                    echo "<tr><td>Целевые</td><td>$value->year</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedTarget)) {
                foreach ($additionalPayedTarget as $value) {
                    echo "<tr><td>Целевые(доп)</td><td>$value->year</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedSingle)) {
                foreach ($payedSingle as $value) {
                    echo "<tr><td>Разовые</td><td>$value->id</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedSingle)) {
                foreach ($payedSingle as $value) {
                    echo "<tr><td>Разовые</td><td>$value->id</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedFiles)) {
                foreach ($payedFiles as $value) {
                    $fine = Table_penalties::findOne(['id' => $value->fine_id]);
                    if ($fine !== null) {
                        switch ($fine->pay_type) {
                            case 'membership':
                                $destination = 'Членские ';
                                break;
                            case 'target':
                                $destination = 'Целевые ';
                                break;
                            default:
                                $destination = 'Эл-во ';
                        }
                        $destination = $fine->period;
                        echo "<tr><td>Пени</td><td>$destination</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                    }
                }
            }

                   echo "</table>
                </td>
            </tr>
            
            ";
            /*echo "<tr><td>$item->id</td>
            <td>" . TimeHandler::getDatetimeFromTimestamp($item->transactionDate) . "</td>
            <td>" . CashHandler::toSmoothRubles($item->transactionSumm) . "</td>
            <td>" . CashHandler::toSmoothRubles($item->usedDeposit) . "</td>
            <td>" . CashHandler::toSmoothRubles($item->gainedDeposit) . "</td></tr>";
            echo "<tr><td colspan='5'></td>
            <table class='table table-condensed table-striped'>
            <caption>Оплачено в транзакции</caption>";
            $payedPower = Table_payed_power::findAll(['transactionId' => $item->id]);
            $additionalPayedPower = Table_additional_payed_power::findAll(['transactionId' => $item->id]);
            $payedMembership = Table_payed_membership::findAll(['transactionId' => $item->id]);
            $additionalPayedMembership = Table_additional_payed_membership::findAll(['transactionId' => $item->id]);
            $payedTarget = Table_payed_target::findAll(['transactionId' => $item->id]);
            $additionalPayedTarget = Table_additional_payed_target::findAll(['transactionId' => $item->id]);
            $payedSingle = Table_payed_single::findAll(['transactionId' => $item->id]);
            $payedFiles = Table_payed_fines::findAll(['transactionId' => $item->id]);
            if (!empty($payedPower)) {
                foreach ($payedPower as $value) {
                    echo "<tr><td>Электроэнергия</td><td>$value->month</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedPower)) {
                foreach ($additionalPayedPower as $value) {
                    echo "<tr><td>Электроэнергия(доп)</td><td>$value->month</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedMembership)) {
                foreach ($payedMembership as $value) {
                    echo "<tr><td>Членские</td><td>$value->quarter</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedMembership)) {
                foreach ($additionalPayedMembership as $value) {
                    echo "<tr><td>Членские(доп)</td><td>$value->quarter</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedTarget)) {
                foreach ($payedTarget as $value) {
                    echo "<tr><td>Целевые</td><td>$value->year</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($additionalPayedTarget)) {
                foreach ($additionalPayedTarget as $value) {
                    echo "<tr><td>Целевые(доп)</td><td>$value->year</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedSingle)) {
                foreach ($payedSingle as $value) {
                    echo "<tr><td>Разовые</td><td>$value->id</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedSingle)) {
                foreach ($payedSingle as $value) {
                    echo "<tr><td>Разовые</td><td>$value->id</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                }
            }
            if (!empty($payedFiles)) {
                foreach ($payedFiles as $value) {
                    $fine = Table_penalties::findOne(['id' => $value->fine_id]);
                    if (!empty($fine)) {
                        switch ($fine->pay_type) {
                            case 'membership':
                                $destination = 'Членские ';
                                break;
                            case 'target':
                                $destination = 'Целевые ';
                                break;
                            default:
                                $destination = 'Эл-во ';
                        }
                        $destination = $fine->period;
                        echo "<tr><td>Пени</td><td>$destination</td><td>" . CashHandler::toSmoothRubles($value->summ) . "</td></tr>";
                    }
                }
            }
            echo "</table></tr>";*/
        }
        echo '</table';
    }
}