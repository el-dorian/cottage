<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:42
 */

use app\assets\BankInvoiceAsset;
use app\models\BankDetails;
use app\models\Calculator;
use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use app\models\TargetHandler;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $info */

/** @var BankDetails $bankInfo */

$payInfo = $info['billInfo']['billInfo'];
$paymentContent = $info['billInfo']['paymentContent'];
$bankInfo = $info['bankInfo'];
$fromDeposit = CashHandler::toRubles($payInfo->depositUsed);
$discount = CashHandler::toRubles($payInfo->discount);
$realSumm = CashHandler::rublesMath(CashHandler::toRubles($payInfo->totalSumm) - $fromDeposit - $discount);
$smoothSumm = CashHandler::toSmoothRubles($realSumm);
$depositText = '';
if (!empty($fromDeposit)) {
    $depositText = '<br/>Оплачено с депозита: ' . CashHandler::toSmoothRubles($fromDeposit);
}
$discountText = '';
if (!empty($discount)) {
    $discountText = '<br/>Скидка: ' . CashHandler::toSmoothRubles($discount);
}

$powerText = '';
$memText = '';
$tarText = '';
$singleText = '';
$finesText = '';

$qr = $bankInfo->drawQR();

if (!empty($paymentContent['power']) || !empty($paymentContent['additionalPower'])) {
    $summ = 0;
    $oldData = null;
    $newData = null;
    $difference = null;
    $usedPower = [];
    $values = '';
    if (!empty($paymentContent['power'])) {
        $summ = $paymentContent['power']['summ'];
        foreach ($paymentContent['power']['values'] as $value) {
            // определю дату оплаты
            $payUp = TimeHandler::getPayUpMonth($value['date']);
            $tempOldData = $value['old-data'];
            $tempNewData = $value['new-data'];
            $oldData = $tempOldData;
            if ($tempNewData >= $oldData) {
                $newData = $tempNewData;
                $difference = $tempNewData - $tempOldData;
                $usedPower[] = ['date' => $value['date'], 'payUp' => $payUp, 'start' => $oldData, 'finish' => $newData, 'difference' => $difference, 'inLimit' => $value['in-limit-cost'], 'overLimit' => $value['over-limit-cost'], 'inLimitSpend' => $value['in-limit'], 'overLimitSpend' => $value['over-limit'], 'cost' => $value['powerCost'], 'overCost' => $value['powerOvercost']];
            } else {
                // очевидно, был заменён счётчик
                $usedPower[] = ['date' => $value['date'], 'payUp' => $payUp, 'start' => $oldData, 'finish' => $newData, 'difference' => $difference, 'inLimit' => $value['in-limit-cost'], 'overLimit' => $value['over-limit-cost'], 'inLimitSpend' => $value['in-limit'], 'overLimitSpend' => $value['over-limit'], 'cost' => $value['powerCost'], 'overCost' => $value['powerOvercost']];
                $oldData = $tempOldData;
                $newData = $tempNewData;
                $difference = $newData - $oldData;

            }
        }
        foreach ($usedPower as $item) {
            $values .= TimeHandler::getFullFromShotMonth($item['date']) .
                ". Показания на начало периода: {$item['start']}" . CashHandler::KW .
                ". Показания на конец периода: {$item['finish']}" . CashHandler::KW .
                ". Итого потреблено: {$item['difference']}" . CashHandler::KW .
                ". На общую сумму: <b>" . CashHandler::toJsRubles(CashHandler::toRubles($item['inLimit']) + CashHandler::toRubles($item['overLimit'])) . '</b>' .
                ". В том числе по соц.норме: {$item['inLimitSpend']} " . CashHandler::KW . " * " . CashHandler::toSmoothRubles($item['cost']) . " = " . CashHandler::toSmoothRubles($item['inLimit']);
            if ($item['overLimit'] > 0) {
                $values .= ". Сверх соц.нормы: {$item['overLimitSpend']} " . CashHandler::KW . " * " . CashHandler::toSmoothRubles($item['overCost']) . " = " . CashHandler::toSmoothRubles($item['overLimit']);

            }
            $values .= ' (срок оплаты: до ' . TimeHandler::getDatetimeFromTimestamp($item['payUp']) . ')';
        }
    }
    if (!empty($paymentContent['additionalPower'])) {
        $values .= 'Дополнительный участок: ';
        $summ = 0;
        $oldData = null;
        $newData = null;
        $difference = null;
        $usedPower = [];
        $summ = $paymentContent['additionalPower']['summ'];
        foreach ($paymentContent['additionalPower']['values'] as $value) {
            // определю дату оплаты
            $payUp = TimeHandler::getPayUpMonth($value['date']);
            $tempOldData = $value['old-data'];
            $tempNewData = $value['new-data'];
            if (empty($oldData)) {
                $oldData = $tempOldData;
            }
            if ($tempNewData >= $oldData) {
                $newData = $tempNewData;
                $difference += $tempNewData - $tempOldData;
            } else {
                // очевидно, был заменён счётчик
                $usedPower[] = ['date' => $value['date'], 'payUp' => $payUp, 'start' => $oldData, 'finish' => $newData, 'difference' => $difference, 'inLimit' => $value['in-limit-cost'], 'overLimit' => $value['over-limit-cost'], 'inLimitSpend' => $value['in-limit'], 'overLimitSpend' => $value['over-limit'], 'cost' => $value['powerCost'], 'overCost' => $value['powerOvercost']];
                $oldData = $tempOldData;
                $newData = $tempNewData;
                $difference = $newData - $oldData;
            }
        }
        $usedPower[] = ['date' => $value['date'], 'payUp' => $payUp, 'start' => $oldData, 'finish' => $newData, 'difference' => $difference, 'inLimit' => $value['in-limit-cost'], 'overLimit' => $value['over-limit-cost'], 'inLimitSpend' => $value['in-limit'], 'overLimitSpend' => $value['over-limit'], 'cost' => $value['powerCost'], 'overCost' => $value['powerOvercost']];
        foreach ($usedPower as $item) {
            $values .= TimeHandler::getFullFromShotMonth($item['date']) .
                ". Показания на начало периода: {$item['start']}" . CashHandler::KW .
                ". Показания на конец периода: {$item['finish']}" . CashHandler::KW .
                ". Итого потреблено: {$item['difference']}" . CashHandler::KW .
                ". На общую сумму: <b>" . CashHandler::toSmoothRubles($summ) . '</b>' .
                ". В том числе по соц.норме: {$item['inLimitSpend']} " . CashHandler::KW . " * " . CashHandler::toSmoothRubles($item['cost']) . " = " . CashHandler::toSmoothRubles($item['inLimit']);
            if ($item['overLimit'] > 0) {
                $values .= ". Сверх соц.нормы: {$item['overLimitSpend']} " . CashHandler::KW . " * " . CashHandler::toSmoothRubles($item['overCost']) . " = " . CashHandler::toSmoothRubles($item['overLimit']);

            }
            $values .= ' (срок оплаты: до ' . TimeHandler::getDatetimeFromTimestamp($item['payUp']) . ')';
        }
        $values .= 'На сумму: ' . CashHandler::toSmoothRubles($summ);
    }
    $powerText = 'Электроэнергия: ' . $values . ' <br/>';
}
if (!empty($paymentContent['membership']) || !empty($paymentContent['additionalMembership'])) {

    $summ = 0;
    $values = '';
    if (!empty($paymentContent['membership'])) {
        $summ += $paymentContent['membership']['summ'];
        foreach ($paymentContent['membership']['values'] as $value) {
            // проверю срок оплаты
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            $values .= '(срок оплаты: до ' . TimeHandler::getPayUpQuarter($value['date']) . ')  ';
            if (TimeHandler::checkOverdueQuarter($value['date'])) {
                $values .= '(платёж просрочен)  ';
            }
        }
    }
    if (!empty($paymentContent['additionalMembership'])) {
        $summ += $paymentContent['additionalMembership']['summ'];
        foreach ($paymentContent['additionalMembership']['values'] as $value) {
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            $values .= '(срок оплаты: до ' . TimeHandler::getPayUpQuarter($value['date']) . ')  ';
            if (TimeHandler::checkOverdueQuarter($value['date'])) {
                $values .= '(платёж просрочен)  ';
            }
        }
    }
    $memText = 'Членские взносы: всего ' . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, -2) . '<br/>';
}
if (!empty($paymentContent['target']) || !empty($paymentContent['additionalTarget'])) {
    $summ = 0;
    $values = '';
    if (!empty($paymentContent['target'])) {
        $summ += $paymentContent['target']['summ'];
        foreach ($paymentContent['target']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            // проверю просроченность платежа
            $payUpTime = TargetHandler::getPayUpTime($value['year']);
            $values .= '(срок оплаты: до ' . TimeHandler::getDatetimeFromTimestamp($payUpTime) . ')  ';
            if ($payUpTime < time()) {
                $values .= '(платёж просрочен)  ';
            }
        }
    }
    if (!empty($paymentContent['additionalTarget'])) {
        $summ += $paymentContent['additionalTarget']['summ'];
        foreach ($paymentContent['additionalTarget']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']) . ', ';
            $payUpTime = TargetHandler::getPayUpTime($value['year']);
            $values .= '(срок оплаты: до ' . TimeHandler::getDatetimeFromTimestamp($payUpTime) . ')  ';
            if ($payUpTime < time()) {
                $values .= '(платёж просрочен)  ';
            }
        }
    }
    $tarText = 'Целевые взносы: всего ' . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, -2) . '<br/>';
}
if (!empty($paymentContent['single'])) {
    $summ = 0;
    $values = '';
    $summ += $paymentContent['single']['summ'];
    foreach ($paymentContent['single']['values'] as $value) {
        $values .= '<b>' . urldecode($value['description']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
    }
    $singleText = 'Дополнительно: всего ' . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, -2) . '<br/>';
}

$fines = Table_view_fines_info::find()->where(['bill_id' => $payInfo->id])->all();
if (!empty($fines)) {
    $finesSumm = 0;
    foreach ($fines as $fine) {
        $finesSumm += $fine->start_summ;
        if ($fine->pay_type === 'membership') {
            $fullPeriod = TimeHandler::getFullFromShortQuarter($fine->period);
        } else if ($fine->pay_type === 'power') {
            $fullPeriod = TimeHandler::getFullFromShotMonth($fine->period);
        } else {
            $fullPeriod = $fine->period;
        }
        $finesText .= FinesHandler::$types[$fine->pay_type] . " за {$fullPeriod} просрочено на 
         " . FinesHandler::getFineDaysLeft(Table_penalties::findOne($fine->fines_id)) . ' дней на сумму ' . CashHandler::toSmoothRubles($fine->start_summ) . ', ';
    }
    $finesText = 'Пени: всего ' . CashHandler::toSmoothRubles($finesSumm) . ', в том числе ' . substr($finesText, 0, -2);
}

$text = "
<div class='description margened'><span>ПАО СБЕРБАНК</span><span class='pull-right''>Форма №ПД-4</span></div>

<div class='text-center bottom-bordered'><b>{$bankInfo->name}</b></div>
<div class='text-center description margened'><span>(Наименование получателя платежа)</span></div>
<div class='bottom-bordered'><span><b>ИНН</b> {$bankInfo->payerInn} <b>КПП</b> {$bankInfo->kpp}</span><span class='pull-right'>{$bankInfo->personalAcc}</span></div>
<div class='description margened'><span>(инн получателя платежа)</span><span class='pull-right'>(номер счёта получателя платежа)</span></div>
<div class='bottom-bordered text-center'><span><b>БИК</b> {$bankInfo->bik} ({$bankInfo->bankName})</span></div>
<div class='text-center description margened'><span>(Наименование банка получателя платежа)</span></div>
<div class='bottom-bordered text-underline'><b>Участок </b>№{$bankInfo->cottageNumber};<b> ФИО:</b> {$bankInfo->lastName}; <b>Назначение:</b> {$bankInfo->purpose};</b></div>
<div class='description margened text-center'><span>(назначение платежа)</span></div>
<div class='text-center bottom-bordered'><b>Сумма: {$smoothSumm}</b></div>
<div class='description margened text-center'><span>(сумма платежа)</span></div>

<div class='description margened'><span>С условиями приёма указанной в платёжном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен. </span><span class='pull-right'>Подпись плательщика <span class='sign-span bottom-bordered'></span></span></div>
";

BankInvoiceAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div id="invoiceWrapper">

    <img class="logo-img" src="/graphics/logo.png" alt="logo">

    <table class="table">
        <tr>
            <td class="leftSide">
                <h3>Извещение</h3>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
        <tr>
            <td class="leftSide">
                <h3>Квитанция</h3>
                <img class="qr-img" src="<?= $qr ?>" alt=""/>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
    </table>

    <div>
        <h4>Детализация платежа по счёту №<?= $payInfo->id . ($info['double'] ? '-a' : '') ?></h4>
        <?= $powerText ?>
        <?= $memText ?>
        <?= $tarText ?>
        <?= $singleText ?>
        <?= $finesText ?>
        <?= $depositText ?>
        <?= $discountText ?>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

