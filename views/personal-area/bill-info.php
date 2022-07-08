<?php

use app\models\CashHandler;
use app\models\Table_payment_bills;
use app\models\Table_transactions;
use app\models\TimeHandler;
use yii\web\View;



/* @var $this View */
/* @var $bill Table_payment_bills */
/* @var $transactions Table_transactions[] */

?>

<table class="table table-condensed table-hover">
    <tbody>
    <tr>
        <td>Номер счёта</td><td><?=$bill->id?></td>
    </tr>
    <tr>
        <td>Полная стоимость счёта</td><td><?= CashHandler::toShortSmoothRubles($bill->totalSumm)?></td>
    </tr>
    <tr>
        <td>Оплачено по счёту</td><td><?= CashHandler::toShortSmoothRubles($bill->payedSumm)?></td>
    </tr>
    <?php
    if($bill->depositUsed > 0){
        echo "<tr><td>Оплачено с депозита</td><td>" . CashHandler::toShortSmoothRubles($bill->depositUsed) . "</td></tr>";
    }
    if($bill->toDeposit > 0){
        echo "<tr><td>Зачислено на депозит</td><td>" . CashHandler::toShortSmoothRubles($bill->toDeposit) . "</td></tr>";
    }
    ?>
    </tbody>
    <tbody>
        <tr><td colspan="2" class="text-center"><h2>Оплаты</h2></td></tr>
        <tr><td colspan="2">
                <?php
                if(!empty($transactions)){
                    echo '<table class="table table-condensed">';
                    foreach ($transactions as $transaction){
                        echo "<tr><td><a href='#' class='ajax-get-trigger' data-action='/personal-area/get-bill-info?id=$bill->id'>#$bill->id</a>$transaction->id</a></td><td>" . CashHandler::toShortSmoothRubles($transaction->transactionSumm) . "</td><td>" . TimeHandler::getDatetimeFromTimestamp($transaction->bankDate) . "</td></tr>";
                    }
                    echo '</table>';
                }
                else{
                    echo '<tr><td colspan="2" class="text-center"><b>Оплат по счёту не было</b></td></tr>';
                }
                ?>
            </td></tr>
    </tbody>
</table>
