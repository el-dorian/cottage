<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

namespace app\models;


use app\models\interfaces\CottageInterface;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use DateTime;
use Exception;
use InvalidArgumentException;
use Yii;
use yii\base\Model;

class Search extends Model
{
    public string $startDate = '';
    public string $finishDate = '';
    public string $searchType = '';
    public array $searchTypeList = ['routine' => 'Обычный', 'summary' => 'Суммарный', 'report' => 'Отчёт', 'differences' => 'Расхождения'];

    public const SCENARIO_BILLS_SEARCH = 'bills-search';

    /**
     * @param $year
     * @return array
     * @throws Exception
     */
    public static function getAccruals($year): array
    {
        if ($year === null) {
            $year = TimeHandler::getThisYear();
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<accruals>';

        $targetMonth = null;
        // получу тариф по целевым за этот год
        $targetTariff = Table_tariffs_target::findOne(['year' => $year]);
        if ($targetTariff !== null) {
            $payUpTime = $targetTariff->payUpTime - 5184000;
            $targetMonth = TimeHandler::getShortMonthFromTimestamp($payUpTime);
        }
        $yearPower = 0;
        $yearMembership = 0;
        $yearTarget = 0;
        $yearAccruals = [];
        $cottages = Cottage::getRegister();
        $additionalCottages = Cottage::getRegister(true);
        $cottages = array_merge($cottages, $additionalCottages);
        // пройдусь по месяцам в году
        $months = TimeHandler::$monthNames;
        foreach ($months as $key => $month) {
            $key = ++$key;
            if ($key < 10) {
                $m = "$year-0$key";
            } else {
                $m = "$year-$key";
            }
            // получу начисления за месяц.
            $monthAccruals = [];
            $monthPowerAccrual = 0;
            $monthMembershipAccrual = 0;
            // электроэнергия
            /** @var CottageInterface $cottage */
            foreach ($cottages as $cottage) {
                if ($cottage->getCottageNumber() === '0') {
                    continue;
                }
                $cottageAccruals = [];
                $totalPowerAccrual = 0;
                if ($cottage->isMain()) {
                    $powerAccrual = Table_power_months::findAll(['cottageNumber' => $cottage->getCottageNumber(), 'month' => $m]);
                } else {
                    $powerAccrual = Table_additional_power_months::findAll(['cottageNumber' => $cottage->getBaseCottageNumber(), 'month' => $m]);
                }
                if (!empty($powerAccrual)) {
                    foreach ($powerAccrual as $item) {
                        $totalPowerAccrual += $item->totalPay;
                    }
                }
                $cottageAccruals['power'] = $totalPowerAccrual;
                $monthPowerAccrual += $totalPowerAccrual;

                $membershipAccrual = 0;
                // начисление членских считаем только каждый третий месяц
                if ((($key + 3) % 3 === 1) && $cottage->getCottageNumber() !== '0') {
                    // буду расчитывать членские взносы
                    $membershipAccrual = MembershipHandler::getAccrued($cottage, $m);
                    $monthMembershipAccrual += $membershipAccrual;
                }
                $cottageAccruals['membership'] = $membershipAccrual;

                $targetAccrual = 0;
                if (substr($targetMonth, 5) === substr($m, 5)) {
                    // посчитаю начисления
                    $targetAccrual = TargetHandler::getAccrued($cottage, $year);
                    $yearTarget += $targetAccrual;
                }
                $cottageAccruals['target'] = $targetAccrual;
                $monthAccruals[$cottage->getCottageNumber()] = $cottageAccruals;
                $xml .= "<accrual><month>$m</month><cottage>{$cottage->getCottageNumber()}</cottage><power>$totalPowerAccrual</power><membership>$membershipAccrual</membership><target>$targetAccrual</target></accrual>";
            }
            $yearAccruals['months'][$month]['totalPower'] = $monthPowerAccrual;
            $yearAccruals['months'][$month]['totalMembership'] = $monthMembershipAccrual;
            $yearAccruals['months'][$month]['totalTarget'] = $yearTarget;
            $yearAccruals['months'][$month]['cottages'] = $monthAccruals;

            $yearPower += $monthPowerAccrual;
            $yearMembership += $monthMembershipAccrual;
        }
        $yearAccruals['power'] = $yearPower;
        $yearAccruals['membership'] = $yearMembership;
        $yearAccruals['target'] = $yearTarget;
        $yearAccruals['wholeAccrual'] = $yearPower + $yearMembership + $yearTarget;
        $xml .= '</accruals>';
        file_put_contents(Yii::$app->basePath . '\\files\\accruals.xml', $xml);
        return $yearAccruals;
    }

    /**
     * @param Table_transactions[]|Table_transactions_double[] $results
     * @return array
     */
    private static function handleTransactions(array $results): array
    {
        $totalSumm = 0;
        $text = '';
        if (!empty($results)) {
            foreach ($results as $result) {
                $totalSumm += CashHandler::toRubles($result->transactionSumm);
                if (!empty($result->bankDate)) {
                    $date = TimeHandler::getDateFromTimestamp($result->bankDate);
                } else {
                    $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                }
                $summ = CashHandler::toShortSmoothRubles($result->transactionSumm);
                if ($result instanceof Table_transactions) {
                    $text .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='$result->billId'>$result->billId</a></td><td><a href='/show-cottage/$result->cottageNumber'>$result->cottageNumber</a></td></td><td><b class='text-info'>$summ</b></td><td><button type='button' data-transaction-id='$result->id' class='btn btn-danger change-date'>Изменить</button></td></tr>";
                } else {
                    $text .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='$result->billId-a'>$result->billId-a</a></td><td><a href='/show-cottage/$result->cottageNumber'>$result->cottageNumber-a</a></td><td><b class='text-info'>$summ</b></td><td><button type='button' data-transaction-id='$result->id' class='btn btn-danger change-date'>Изменить</button></td></tr>";
                }
            }
        }
        return ['text' => $text, 'summ' => $totalSumm];

    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_BILLS_SEARCH => ['startDate', 'finishDate', 'searchType'],
        ];
    }

    public function rules(): array
    {
        return [
            [['startDate', 'finishDate', 'searchType'], 'required'],
            [['startDate', 'finishDate'], 'date', 'format' => 'y-M-d'],
            ['searchType', 'in', 'range' => ['routine', 'summary', 'report', 'differences']]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'startDate' => 'Начало периода',
            'finishDate' => 'Конец периода',
            'searchType' => 'Тип отчёта',
        ];
    }


    /**
     * @return array
     * @throws Exception
     */
    public function doSearch(): array
    {
            $start = new DateTime('0:0:00' . $this->startDate);
            $finish = new DateTime('23:59:50' . $this->finishDate);
            $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];
        switch ($this->searchType) {
            case 'summary' :
                return $this->getSummary($interval);
            case 'report':
                return $this->getReport($interval);
            case 'routine':
                return $this->getTransactions($interval);
            case 'differences':
                return $this->getDifferences($interval);
        }
        throw new InvalidArgumentException("Неверный тип отчёта");
    }

    private function getTransactions($interval): array
    {
        $results = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        if (!empty($results || !empty($doubleResults))) {
            $totalSumm = 0;
            $content = "<table class='table table-striped'><thead><th>Дата платежа</th><th>№</th><th>Участок</th><th>Сумма</th><th>Дата</th></thead><tbody>";
            $data = self::handleTransactions($results);
            $content .= $data['text'];
            $totalSumm += $data['summ'];
            $data = self::handleTransactions($doubleResults);
            $content .= $data['text'];
            $totalSumm += $data['summ'];
            $content .= '</tbody></table>';
            return ['status' => 1, 'data' => $content, 'totalSumm' => $totalSumm, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
        }

        return ['status' => 1, 'data' => '<h2>Транзакций за период не было</h2>', 'totalSumm' => 0, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
    }

    private function getSummary($interval): array
    {
        $totalPowerSumm = 0;
        $totalMemSumm = 0;
        $totalTargetSumm = 0;
        $totalSingleSumm = 0;
        $totalFinesSumm = 0;
        $discountsSumm = 0;
        $toDepositSumm = 0;
        $fromDepositSumm = 0;
        $powerDetails = [];
        $membershipDetails = [];
        $targetDetails = [];

        // найду транзакции
        $results = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $results = array_merge($results, $doubleResults);
        if (!empty($results)) {
            foreach ($results as $result) {
                // найду все сущности, привязанные к транзакции
                if ($result instanceof Table_transactions) {
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $result->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $result->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $result->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $result->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $result->id])->all();
                    $discounts = Table_discounts::find()->where(['transactionId' => $result->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'destination' => 'in'])->all();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'destination' => 'out'])->all();
                } else {
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $result->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $result->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $result->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $result->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $result->id . '-a'])->all();
                    $discounts = Table_discounts::find()->where(['transactionId' => $result->id . '-a'])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'billId' => $result->billId, 'destination' => 'in'])->all();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'billId' => $result->billId, 'destination' => 'out'])->all();
                }
                if (!empty($powers)) {
                    /** @var Table_payed_power $p */
                    foreach ($powers as $p) {
                        if (!empty($powerDetails[$p->month])) {
                            $powerDetails[$p->month] += $p->summ;
                        } else {
                            $powerDetails[$p->month] = $p->summ;
                        }
                        // посчитаю детали
                        $totalPowerSumm = CashHandler::toRubles($totalPowerSumm) + CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($memberships)) {
                    /** @var Table_payed_membership $p */
                    foreach ($memberships as $p) {
                        if (!empty($membershipDetails[$p->quarter])) {
                            $membershipDetails[$p->quarter] += $p->summ;
                        } else {
                            $membershipDetails[$p->quarter] = $p->summ;
                        }
                        $totalMemSumm = CashHandler::toRubles($totalMemSumm) + CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($targets)) {
                    /** @var Table_payed_target $p */
                    foreach ($targets as $p) {
                        if (!empty($targetDetails[$p->year])) {
                            $targetDetails[$p->year] += $p->summ;
                        } else {
                            $targetDetails[$p->year] = $p->summ;
                        }
                        $totalTargetSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($singles)) {
                    foreach ($singles as $p) {
                        $totalSingleSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($fines)) {
                    foreach ($fines as $p) {
                        $totalFinesSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($discounts)) {
                    foreach ($discounts as $p) {
                        $discountsSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($toDeposit)) {
                    foreach ($toDeposit as $p) {
                        $toDepositSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if (!empty($fromDeposit)) {
                    foreach ($fromDeposit as $p) {
                        $fromDepositSumm += CashHandler::toRubles($p->summ);
                    }
                }
            }
            $powerDetailsValue = '<br/>Детали:';
            if (!empty($powerDetails)) {
                ksort($powerDetails);
                foreach ($powerDetails as $key => $value) {
                    $powerDetailsValue .= "<br/><b class='text-info'>$key:</b> " . CashHandler::toShortSmoothRubles($value);
                }
            }
            $membershipDetailsValue = '<br/>Детали:';
            if (!empty($membershipDetails)) {
                ksort($membershipDetails);
                foreach ($membershipDetails as $key => $value) {
                    $membershipDetailsValue .= "<br/><b class='text-info'>$key:</b> " . CashHandler::toShortSmoothRubles($value);
                }
            }
            $targetDetailsValue = '<br/>Детали:';
            if (!empty($targetDetails)) {
                ksort($targetDetails);
                foreach ($targetDetails as $key => $value) {
                    $targetDetailsValue .= "<br/><b class='text-info'>$key:</b> " . CashHandler::toShortSmoothRubles($value);
                }
            }
            $content = "<table class='table table-striped'><thead><th>Электроэнергия</th><th>Членские</th><th>Целевые</th><th>Разовые</th><th>Пени</th><th>С депозита</th><th>На депозит</th></thead><tbody>";

            $content .= '<tr><td>' . CashHandler::toRubles($totalPowerSumm) . $powerDetailsValue . '</td><td>' . CashHandler::toRubles($totalMemSumm) . $membershipDetailsValue . '</td><td>' . CashHandler::toRubles($totalTargetSumm) . $targetDetailsValue . '</td><td>' . CashHandler::toRubles($totalSingleSumm) . '</td><td>' . CashHandler::toRubles($totalFinesSumm) . '</td><td>' . CashHandler::toRubles($fromDepositSumm) . '</td><td>' . CashHandler::toRubles($toDepositSumm) . '</td></tr></tbody></table>';
            $total = CashHandler::toRubles($totalSingleSumm + $totalPowerSumm + $totalTargetSumm + $totalFinesSumm + $totalMemSumm - $discountsSumm - $fromDepositSumm + $toDepositSumm, true);
            $totalContent = 'total single sum: ' .  $totalSingleSumm . ' total power sum: ' . $totalPowerSumm . ' total target sum: ' . $totalTargetSumm . ' total fines sum: ' . $totalFinesSumm . ' total membership sum: ' . $totalMemSumm . ' discount sum: ' . $discountsSumm . 'from deposit sum: ' . $fromDepositSumm . 'to deposit sum: ' . $toDepositSumm;
                return ['status' => 1,'total_content' => $totalContent , 'data' => $content, 'totalSumm' => $total, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
        }
        return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
    }

    /**
     * @param $interval
     * @return array
     */
    private function getReport($interval): ?array
    {
        $wholePower = 0;
        $wholeTarget = 0;
        $wholeSingle = 0;
        $wholeMembership = 0;
        $wholeFines = 0;
        $wholeSumm = 0;
        $fullSumm = 0;
        $wholeDeposit = 0;
        // найду транзакции за день по банковскому отчёту
        $transactions = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->orderBy('bankDate')->all();
        $trs = Table_transactions_double::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $transactions = array_merge($transactions, $trs);

        if (!empty($transactions)) {
            $content = [];
            foreach ($transactions as $transaction) {
                $wholeSumm += CashHandler::toRubles($transaction->transactionSumm);
                $fullSumm += CashHandler::toRubles($transaction->transactionSumm);
                if ($transaction instanceof Table_transactions) {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'billId' => $transaction->billId, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'billId' => $transaction->billId, 'destination' => 'out'])->one();
                    if (!empty($memberships)) {
                        $memSumm = 0;
                        $memList = '';
                        foreach ($memberships as $membership) {
                            if ($membership instanceof Table_payed_membership) {
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                $memList .= '(Д) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $memSumm += $membership->summ;
                            $wholeMembership += CashHandler::toRubles($membership->summ);
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
                        foreach ($powers as $power) {
                            if ($power instanceof Table_payed_power) {
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if ($powData === null) {
                                    echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Д) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                            }
                            $powUsed .= $powData->difference . '<br/>';
                            $powSumm += $power->summ;
                            $powSumm = CashHandler::toRubles($powSumm);
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
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
                                $tarList .= '(Д) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
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
                    /** @var Table_deposit_io $fromDeposit */
                    if ($fromDeposit !== null) {
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    } else {
                        $fromDepositSumm = 0;
                    }
                    /** @var Table_deposit_io $toDeposit */
                    if ($toDeposit !== null) {
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    } else {
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>$transaction->billId</td><td class='cottage-number-cell'>$transaction->cottageNumber</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td></tr>';
                } else {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'billId' => $transaction->billId, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'billId' => $transaction->billId, 'destination' => 'out'])->one();
                    if (!empty($memberships)) {
                        $memSumm = 0;
                        $memList = '';
                        /** @var Table_payed_membership $membership */
                        foreach ($memberships as $membership) {
                            if ($membership instanceof Table_payed_membership) {
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                $memList .= '(Д) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
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
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Д) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                            }
                            $powUsed .= $powData->difference . '<br/>';
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
                        /** @var Table_payed_target $target */
                        foreach ($targets as $target) {
                            if ($target instanceof Table_payed_target) {
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            } else {
                                $tarList .= '(Д) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
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
                    /** @var Table_deposit_io $fromDeposit */
                    if ($fromDeposit !== null) {
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    } else {
                        $fromDepositSumm = 0;
                    }
                    /** @var Table_deposit_io $toDeposit */
                    if ($toDeposit !== null) {
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    } else {
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>$transaction->billId-a</td><td class='cottage-number-cell'>$transaction->cottageNumber-a</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td></tr>';
                }
            }
            $content[] = "<tr><td class='date-cell'>Итого</td><td class='bill-id-cell'></td><td class='cottage-number-cell'></td><td class='quarter-cell'></td><td class='mem-summ-cell'>$wholeMembership</td><td class='pow-values'></td><td class='pow-total'></td><td class='pow-summ'>$wholePower</td><td class='target-by-years-cell'></td><td class='target-total'>$wholeTarget</td><td></td><td>$wholeSingle</td><td></td><td>$wholeFines</td><td>$wholeDeposit</td><td>$wholeSumm</td></tr>";
            return ['status' => 1, 'data' => $content, 'totalSumm' => $fullSumm, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
        }
        return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
    }

    private function getDifferences(array $interval): array
    {
        $content  = "<table class='table table-striped'><thead><th>День</th><th>Сумма по транзакциям</th><th>Сумма зачисленного</th><th>Расхождение</th><th>Разница</th></thead><tbody>";
        // explode interval by days
        $daysInInterval = TimeHandler::explodeIntervalByDays($interval);
        foreach ($daysInInterval as $key => $item) {
            // найду транзакции по банковскому отчёту
            $transactions = Table_transactions::find()->where(['>=', 'bankDate', $item['start']])->andWhere(['<=', 'bankDate', $item['end']])->orderBy('bankDate')->all();
            $trs = Table_transactions_double::find()->where(['>=', 'bankDate', $item['start']])->andWhere(['<=', 'bankDate', $item['end']])->all();
            $transactions = array_merge($transactions, $trs);
            // найду зачисления
            $totallyGain = 0;
            if(!empty($transactions)){
                foreach ($transactions as $transaction) {
                    $totallyGain += CashHandler::toRubles($transaction->transactionSumm);
                }
            }
            $summary = $this->getSummary(['start' => $item['start'], 'finish' => $item['end']]);
            $isDifference = CashHandler::toRubles($summary['totalSumm']) === CashHandler::toRubles($totallyGain) ? '<b class="text-success">Нет</b>' : '<b class="text-danger">Да</b>';
            // теперь посчитаю всё учтённое за данный период
            $content .= "<tr><td>$key</td><td>" . CashHandler::toRubles($totallyGain) . "</td><td>{$summary['totalSumm']}</td><td>$isDifference</td><td>" . CashHandler::toRubles(CashHandler::toRubles($summary['totalSumm']) - CashHandler::toRubles($totallyGain), true) . "</td></tr>";
            if($isDifference === '<b class="text-danger">Да</b>'){
                // добавлю репорт за день
//                $report = $this->getReport(['start' => $item['start'], 'finish' => $item['end']]);
//
//                if (!empty($report['data'])) {
//                    $content .= "<tr><td colspan='5'>";
//                    $content .= '<table class="table table-bordered table-condensed little-text small-text">
//                        <thead>
//                        <tr>
//                            <th rowspan="2" class="text-center vertical-top">Дата</th>
//                            <th rowspan="2" class="text-center vertical-top">№</th>
//                            <th rowspan="2" class="text-center vertical-top">Уч.</th>
//                            <th colspan="2" class="text-center">Членские</th>
//                            <th colspan="3" class="text-center">Электричество</th>
//                            <th colspan="2" class="text-center">Целевые</th>
//                            <th colspan="2" class="text-center">Разовые</th>
//                            <th colspan="2" class="text-center">Пени</th>
//                            <th rowspan="2" class="text-center vertical-top">Деп</th>
//                            <th rowspan="2" class="text-center vertical-top">Итого</th>
//                        </tr>
//                        <tr>
//                            <th class="text-center">Покварт.</th>
//                            <th class="text-center">Итого</th>
//                            <th class="text-center">Показ.</th>
//                            <th class="text-center">Всего</th>
//                            <th class="text-center">Опл.</th>
//                            <th class="text-center">По годам</th>
//                            <th class="text-center">Итого</th>
//                            <th class="text-center">Дет.</th>
//                            <th class="text-center">Итог</th>
//                            <th class="text-center">Дет.</th>
//                            <th class="text-center">Итог</th>
//                        </tr>
//                        </thead>
//                        <tbody>
//                        ' . implode('', $report['data']);
//						}
//                $content .= "</tbody></table>";
//                $content .= "</td></tr>";
                
                // придётся разбирать каждую транзакцию
                /** @var Table_transactions $potentialFailedTransaction */
                $content  .= "<tr><td colspan='5'><table class='table table-striped'><thead><th>Участок</th><th>Транзакция</th><th>Счёт</th><th>Сумма транзакции</th><th>Зачисленная сумма</th><th>Разница</th></thead><tbody>";
                foreach ($transactions as $potentialFailedTransaction) {
                    $payedInTransaction = 0;
                    // get payed power
                    $payedInTransaction += CashHandler::toRubles($potentialFailedTransaction->gainedDeposit);
                    $payedInTransaction -= CashHandler::toRubles($potentialFailedTransaction->usedDeposit);

                    $payedPowerItems = Table_payed_power::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedPowerItems)){
                        foreach ($payedPowerItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    $payedPowerItems = Table_additional_payed_power::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedPowerItems)){
                        foreach ($payedPowerItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    $payedMembershipItems = Table_payed_membership::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedMembershipItems)){
                        foreach ($payedMembershipItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    $payedMembershipItems = Table_additional_payed_membership::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedMembershipItems)){
                        foreach ($payedMembershipItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    $payedTargetItems = Table_payed_target::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedTargetItems)){
                        foreach ($payedTargetItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    $payedFineItems = Table_payed_fines::getPayedInTransaction($potentialFailedTransaction->id);
                    if(!empty($payedFineItems)){
                        foreach ($payedFineItems as $payedPowerItem) {
                            $payedInTransaction += CashHandler::toRubles($payedPowerItem->summ);
                        }
                    }
                    if(CashHandler::toRubles($potentialFailedTransaction->transactionSumm) !== CashHandler::toRubles($payedInTransaction)){
                        $content .= "<tr><td>$potentialFailedTransaction->cottageNumber</td><td>$potentialFailedTransaction->id</td><td>$potentialFailedTransaction->billId</td><td>{$potentialFailedTransaction->transactionSumm}</td><td>$payedInTransaction</td><td>" . CashHandler::toRubles(CashHandler::toRubles($potentialFailedTransaction->transactionSumm) - CashHandler::toRubles($payedInTransaction), true) . "</td></tr>";

                    }
                }
                $content .= "</tbody></table>";
                $content .= "</td></tr>";
            }
        }


        $content .= "</tbody></table>";
        return ['status' => 1, 'data' => $content, 'totalSumm' => $totallyGain, 'from' => TimeHandler::getDatetimeFromTimestamp($interval['start']), 'to' => TimeHandler::getDatetimeFromTimestamp($interval['finish'])];
    }
}