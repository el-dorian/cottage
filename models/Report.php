<?php
/** @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:08
 */

namespace app\models;

use app\models\interfaces\CottageInterface;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class Report extends Model
{

    /**
     * @param $start
     * @param $end
     * @param CottageInterface $cottage
     * @return array
     */
    public static function cottageReport($start, $end, CottageInterface $cottage): array
    {
        $content = [];
        $singleDescriptions = [];
        $singleCounters = 1;
        // найду все транзакции данного участка за выбранный период
        $transactions = TransactionsHandler::getTransactionsByPeriod($cottage, $start, $end);
        if ($transactions !== null) {
            // отчёты
            $wholePower = 0;
            $wholeTarget = 0;
            $wholeMembership = 0;
            $wholeSingle = 0;
            $wholeFines = 0;
            $wholeSumm = 0;
            $wholeDeposit = 0;
            foreach ($transactions as $transaction) {
                // вычислю полную сумму заплаченного
                $wholeSumm += CashHandler::toRubles($transaction->transactionSumm);
                if ($transaction instanceof Table_transactions) {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->all();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out', 'cottageNumber' => $transaction->cottageNumber])->one();
                    if (!empty($memberships)) {
                        // если были оплаты по членским платежам
                        // полная сумма оплат
                        $memSumm = 0;
                        // список оплаченных месяцев
                        $memList = '';
                        foreach ($memberships as $membership) {
                            // если оплачен счёт по основному участку
                            if ($membership instanceof Table_payed_membership) {
                                // занесу в список сумму оплаты по участку
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                // занесу в список сумму оплаты по дополнительному участку
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            // добавлю сумму оплаты к общей стоимости оплаты за членские взносы в платеже
                            $memSumm += $membership->summ;
                            // добавлю сумму оплаты к общей стоимости оплаты за членские взносы в целом
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                        }
                        // отформатирую сумму оплаты
                        $memSumm = CashHandler::toRubles($memSumm);
                    } else {
                        // иначе отмечу, что ничего не оплачено
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if (!empty($powers)) {
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        foreach ($powers as $power) {
                            if ($power instanceof Table_payed_power) {
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if ($powData === null) {
                                    // если не найден период - выдам ошибку
                                    echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                            }
                            $powSumm += $power->summ;
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
                        $powSumm = CashHandler::toRubles($powSumm);
                    } else {
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if (!empty($targets)) {
                        $tarSumm = 0;
                        $tarList = '';
                        foreach ($targets as $target) {
                            if ($target instanceof Table_payed_target) {
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            } else {
                                $tarList .= '(Доп) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    } else {
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if (!empty($singles)) {
                        $singleSumm = 0;
                        $singleList = '';
                        /** @var Table_payed_single $single */
                        foreach ($singles as $single) {
                            $singleSumm += $single->summ;
                            $wholeSingle += $singleSumm;
                            // получу назначение платежа
                            $billInfo = Table_payment_bills::findOne(['id' => $transaction->billId]);
                            if($billInfo === null){
                                echo "счёт {$transaction->billId} - не найден";
                                die;
                            }
                            $xml = new DOMHandler($billInfo->bill_content);
                            $name = $xml->query("//pay[@timestamp='" . $single->time . "']");
                            $attrs = DOMHandler::getElemAttributes($name->item(0));
                            $description = urldecode($attrs['description']);
                            $singleList .= "($singleCounters)* " . CashHandler::toRubles($single->summ) . '<br/>';
                            $singleCounters++;
                            $singleDescriptions[] = $description;

                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);

                    } else {
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if (!empty($fines)) {
                        $finesSumm = 0;
                        $finesList = '';
                        /** @var Table_payed_fines $fine */
                        foreach ($fines as $fine) {
                            // найду информацию о пени
                            $fineInfo = Table_penalties::findOne($fine->fine_id);
                            if($fineInfo === null){
                                echo " пени {$fine->fine_id} - не найдены";
                                die;
                            }
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    } else {
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if (!empty($fromDeposit)) {
                        // тут нужно проверить, не было ли ранее транзакции на сумму с депозита по этому счёту
                        $thisBillTransactions = Table_transactions::getBillTransactions($cottage, $transaction->billId);
                        if(!empty($thisBillTransactions) && count($thisBillTransactions) > 1 && $thisBillTransactions[0]->transactionSumm === 0 && $thisBillTransactions[0]->id !== $transaction->id){
                            //todo тут надо будет проводить проверку на соответствие суммы потраченного депозита
                            $fromDepositSumm = 0;
                        }
                        else{
                            $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                            $wholeDeposit = CashHandler::toRubles($wholeDeposit - CashHandler::toRubles($fromDeposit->summ, true), true);
                        }
                    } else {
                        $fromDepositSumm = 0;
                    }
                    $toDepositComposed = 0;
                    /** @var Table_deposit_io $toDeposit */
                    if ($toDeposit !== null) {
                        /** @var Table_deposit_io $item */
                        foreach ($toDeposit as $item) {
                            $toDepositComposed += CashHandler::toRubles($item->summ);
                        }
                        $wholeDeposit = CashHandler::toRubles($wholeDeposit + CashHandler::toRubles($toDepositComposed, true), true);
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositComposed - $fromDepositSumm, true);

                    $content[] = "
                        <tr>
                            <td class='date-cell'>$date</td>
                            <td class='bill-id-cell'>{$transaction->billId}</td>
                            <td class='quarter-cell'>$memList</td>
                            <td class='mem-summ-cell'>$memSumm</td>
                            <td class='pow-values'>" . $powCounterValue . "</td>
                            <td class='pow-total'>" . $powUsed . "</td>
                            <td class='pow-summ'>$powSumm</td>
                            <td class='target-by-years-cell'>$tarList</td>
                            <td class='target-total'>$tarSumm</td>
                            <td>$singleList</td>
                            <td>$singleSumm</td>
                            <td>$finesList</td>
                            <td>$finesSumm</td>
                            <td>$totalDeposit</td>
                            <td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td>
                        </tr>';
                }
                else {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out', 'cottageNumber' => $transaction->cottageNumber])->one();
                    if (!empty($memberships)) {
                        $memSumm = 0;
                        $memList = '';
                        /** @var Table_payed_membership $membership */
                        foreach ($memberships as $membership) {
                            if ($membership instanceof Table_payed_membership) {
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                            $memSumm += $membership->summ;
                        }
                        $memSumm = CashHandler::toRubles($memSumm);
                    } else {
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if (!empty($powers)) {
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        /** @var Table_payed_power $power */
                        foreach ($powers as $power) {
                            if ($power instanceof Table_payed_power) {
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if ($powData === null) {
                                    echo $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
                        $powSumm = CashHandler::toRubles($powSumm);
                    } else {
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if (!empty($targets)) {
                        $tarSumm = 0;
                        $tarList = '';
                        /** @var Table_payed_target $target */
                        foreach ($targets as $target) {
                            if ($target instanceof Table_payed_target) {
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            } else {
                                $tarList .= '(Доп) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    } else {
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if (!empty($singles)) {
                        $singleSumm = 0;
                        $singleList = '';
                        /** @var Table_payed_single $single */
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                            $wholeSingle += $singleSumm;
                            // получу назначение платежа
                            $billInfo = Table_payment_bills::findOne(['id' => $transaction->billId]);
                            $xml = new DOMHandler($billInfo->bill_content);
                            $name = $xml->query("/pay[@timestamp='" . $single->time . "']");
                            $attrs = DOMHandler::getElemAttributes($name->item(0));
                            $description = urldecode($attrs['description']);
                            $singleList .= "($singleCounters)* " . CashHandler::toRubles($single->summ) . '<br/>';
                            $singleCounters++;
                            $singleDescriptions[] = $description;
                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);
                    } else {
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if (!empty($fines)) {
                        $finesSumm = 0;
                        $finesList = '';
                        /** @var Table_payed_fines $fine */
                        foreach ($fines as $fine) {
                            // найду информацию о пени
                            $fineInfo = Table_penalties::findOne($fine->fine_id);
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    } else {
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if (!empty($fromDeposit)) {
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit = CashHandler::toRubles($wholeDeposit - CashHandler::toRubles($fromDeposit->summ, true), true);
                    } else {
                        $fromDepositSumm = 0;
                    }
                    if (!empty($toDeposit)) {
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit = CashHandler::toRubles($wholeDeposit + CashHandler::toRubles($toDeposit->summ, true), true);
                    } else {
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "
                                    <tr>
                                        <td class='date-cell'>$date</td>
                                        <td class='bill-id-cell'>{$transaction->billId}-a</td>
                                        <td class='quarter-cell'>$memList</td>
                                        <td class='mem-summ-cell'>$memSumm</td>
                                        <td class='pow-values'>$powCounterValue</td>
                                        <td class='pow-total'>$powUsed</td>
                                        <td class='pow-summ'>$powSumm</td>
                                        <td class='target-by-years-cell'>$tarList</td>
                                        <td class='target-total'>$tarSumm</td>
                                        <td>$singleList</td>
                                        <td>$singleSumm</td>
                                        <td>$finesList</td>
                                        <td>$finesSumm</td>
                                        <td>$totalDeposit</td>
                                        <td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td>
                                    </tr>';
                }
            }
            $content[] = "
                            <tr>
                                <td class='date-cell'>Итого</td>
                                <td class='bill-id-cell'></td>
                                <td class='quarter-cell'></td>
                                <td class='mem-summ-cell'>$wholeMembership</td>
                                <td class='pow-values'></td>
                                <td class='pow-total'></td>
                                <td class='pow-summ'>$wholePower</td>
                                <td class='target-by-years-cell'></td>
                                <td class='target-total'>$wholeTarget</td>
                                <td></td>
                                <td>$wholeSingle</td>
                                <td></td>
                                <td>$wholeFines</td>
                                <td>" . CashHandler::toRubles($wholeDeposit, true) . "</td>
                                <td>$wholeSumm</td>
                            </tr>";
        }
        return ['content' => $content, 'cottageInfo' => $cottage, 'singleDescriptions' => $singleDescriptions];
    }

    /**
     * @param $cottageNumber
     * @return string
     */
    public static function powerDebtReport($cottageNumber): string
    {

        $content = "<table class='table table-hover table-striped'><thead><tr><th>Месяц</th><th>Данные</th><th>Потрачено</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead>
<tbody>";
        $info = PowerHandler::getDebtReport(Cottage::getCottageByLiteral($cottageNumber));
        foreach ($info as $item) {
            $inLimitPay = CashHandler::toShortSmoothRubles($item->powerData->inLimitPay);
            $overLimitPay = CashHandler::toShortSmoothRubles($item->powerData->overLimitPay);
            $totalPay = CashHandler::toShortSmoothRubles($item->powerData->totalPay);

            $date = TimeHandler::getFullFromShotMonth($item->powerData->month);
            $content .= "<tr><td>$date</td><td>{$item->powerData->newPowerData} кВт.ч</td><td>{$item->powerData->difference} кВт.ч</td><td>$inLimitPay</td><td>$overLimitPay</td><td>$totalPay</td></tr>";
        }
        $content .= '</tbody></table>';
        return $content;
    }


    public static function power_additionalDebtReport($cottageNumber): string
    {

        $content = "<table class='table table-hover table-striped'><thead><tr><th>Месяц</th><th>Данные</th><th>Потрачено</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead>
<tbody>";
        $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        $info = PowerHandler::getDebtReport($cottageInfo);
        foreach ($info as $item) {
            $inLimitPay = CashHandler::toShortSmoothRubles($item->powerData->inLimitPay);
            $overLimitPay = CashHandler::toShortSmoothRubles($item->powerData->overLimitPay);
            $totalPay = CashHandler::toShortSmoothRubles($item->powerData->totalPay);

            $date = TimeHandler::getFullFromShotMonth($item->powerData->month);
            $content .= "<tr><td>$date</td><td>{$item->powerData->newPowerData} кВт.ч</td><td>{$item->powerData->difference} кВт.ч</td><td>$inLimitPay</td><td>$overLimitPay</td><td>$totalPay</td></tr>";
        }
        $content .= '</tbody></table>';
        return $content;
    }

    /**
     * @param $cottageNumber
     * @return bool|string
     */
    public static function membershipDebtReport($cottageNumber)
    {
        $cottageInfo = Table_cottages::findOne($cottageNumber);
        if ($cottageInfo !== null) {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Квартал</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
            $info = MembershipHandler::getDebt($cottageInfo);
            foreach ($info as $item) {
                $fixed = CashHandler::toShortSmoothRubles($item->tariffFixed);
                $float = CashHandler::toShortSmoothRubles($item->tariffFloat);
                $floatSumm = CashHandler::toShortSmoothRubles($item->amount - ($item->tariffFixed));
                $totalSumm = CashHandler::toShortSmoothRubles($item->amount);
                $content .= "<tr><td>$item->quarter</td><td>{$cottageInfo->cottageSquare}</td><td>$fixed</td><td>$float</td><td>$fixed</td><td>$floatSumm</td><td>$totalSumm</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
        }
        return false;
    }

    public static function membership_additionalDebtReport($cottageNumber)
    {
        $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
        if ($cottageInfo !== null) {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Квартал</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
            $info = MembershipHandler::getDebt($cottageInfo);
            foreach ($info as $key => $item) {
                $content .= "<tr><td>{$item->quarter}</td><td>{$cottageInfo->cottageSquare}</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->amount}  &#8381;</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
        }
        return false;
    }

    /**
     * @param $cottageNumber int|string
     * @return string
     */
    public static function targetDebtReport($cottageNumber): string
    {
        $cottageInfo = Table_cottages::findOne($cottageNumber);
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Год</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th><th>Уже оплачено</th></tr></thead><tbody>";
        if ($cottageInfo !== null) {
            $years = TargetHandler::getDebt($cottageInfo);
            foreach ($years as $item) {
                $content .= "<tr><td>{$item->year}</td><td>{$cottageInfo->cottageSquare}</td><td>{$item->tariffFixed} &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->amount}&#8381;</td><td>{$item->partialPayed}&#8381;</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
        }
        throw new InvalidArgumentException('Неверный адрес участка');
    }

    /**
     * @param $cottageNumber
     * @return string
     */
    public static function target_additionalDebtReport($cottageNumber): string
    {
        $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Год</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
        if ($cottageInfo !== null) {
            $years = TargetHandler::getDebt($cottageInfo);
            foreach ($years as $key => $year) {
                $content .= "<tr><td>{$key}</td><td>{$cottageInfo->cottageSquare}</td><td>{$year->tariffFixed} &#8381;</td><td>{$year->tariffFloat}  &#8381;</td><td>{$year->tariffFixed}  &#8381;</td><td>{$year->tariffFloat}  &#8381;</td><td>{$year->amount}  &#8381;</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
        }
        throw new InvalidArgumentException('Неверный адрес участка');
    }

    /**
     * @param $cottageNumber int|string
     * @return string
     */
    public static function singleDebtReport($cottageNumber): string
    {
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Дата</th><th>Цена</th><th>Цель</th></tr></thead><tbody>";
        $duty = SingleHandler::getDebtReport($cottageNumber);

        foreach ($duty as $value) {
            $date = TimeHandler::getDateFromTimestamp($value->time);
            $summ = $value->amount;
            $payed = $value->partialPayed;
            $description = urldecode($value->description);
            $realSumm = CashHandler::rublesMath($summ - $payed);
            $content .= "<tr class='single-item' data-id='{$value->time}'><td>$date</td><td>{$realSumm}  &#8381;</td><td>{$description}</td></tr>";

        }
        $content .= '</tbody></table>';
        return $content;
    }

    public static function single_additionalDebtReport($cottageNumber): string
    {
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Дата</th><th>Цена</th><th>Цель</th></tr></thead><tbody>";
        $duty = SingleHandler::getDebtReport($cottageNumber, true);
        foreach ($duty as $key => $value) {
            $date = TimeHandler::getDateFromTimestamp($key);
            $summ = $value['summ'];
            $payed = $value['payed'];
            $description = urldecode($value['description']);
            $realSumm = CashHandler::rublesMath($summ - $payed);
            $content .= "<tr class='single-item' data-id='$key'><td>$date</td><td>{$realSumm}  &#8381;</td><td>{$description}</td></tr>";

        }
        $content .= '</tbody></table>';
        return $content;
    }
}