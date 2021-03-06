<?php

use app\assets\TargetReportAsset;
use app\models\database\Accruals_target;
use app\models\Table_cottages;
use app\models\TimeHandler;
use yii\web\View;

TargetReportAsset::register($this);

/* @var $this View */
/* @var $debtors Table_cottages[] */

echo '<table class="table table-striped"><tbody>';
foreach ($debtors as $debtor) {
    echo "<tr class='debtor' data-cottage='$debtor->cottageNumber'><td class='debt-owner' data-cottage='$debtor->cottageNumber'>$debtor->cottageNumber</td><td>" . Accruals_target::getItem($debtor, TimeHandler::getThisYear())->countLeftPayed() . "</td><td class='status' data-cottage='$debtor->cottageNumber'>Не отправлено</td><td><label>Отправить <input type='checkbox' checked class='accept-send'></label></td></tr>";
}
echo '</tbody></table>';
echo '<button id="sendBtn" class="btn btn-default"><span class="text-success">Отправить напоминания</span></button>';
echo '<button id="clearBtn" class="btn btn-default"><span class="text-danger">Снять галочки</span></button>';
