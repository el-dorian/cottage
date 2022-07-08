<?php

use app\assets\PersonalAreaAsset;
use app\models\CashHandler;
use app\models\GrammarHandler;
use app\models\personal_area\PersonalInfo;
use app\widgets\PersonalBillsWidget;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

/* @var $this View */
/* @var $info PersonalInfo */

PersonalAreaAsset::register($this);
ShowLoadingAsset::register($this);
?>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Статус</a></li>
    <li><a href="#electricity" data-toggle="tab">Электроэнергия</a></li>
    <li><a href="#membership" data-toggle="tab">Членские взносы</a></li>
    <li><a href="#target" data-toggle="tab">Целевые платежи</a></li>
    <li><a href="#fines" data-toggle="tab">Пени</a></li>
    <li><a href="#bills" data-toggle="tab">Счета</a></li>

</ul>

<div class="tab-content">
    <div class="tab-pane active" id="global_actions">
        <h1 class="text-center">Участок <?= $info->cottage->getCottageNumber() ?></h1>
        <div class="row">
            <div class="col-sm-12">
                <table class="table table-condensed table-hover">
                    <caption>Информация</caption>
                    <tbody>
                    <tr>
                        <td>Текущие показания счётчика электроэнергии: </td><td><b class="text-success"><?=GrammarHandler::handleCounterData($info->additionalCottageInfo->current_counter_indication)?></b></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane" id="electricity">
        <h1>you here</h1>
    </div>
    <div class="tab-pane" id="membership">
        <h1>you her</h1>
    </div>
    <div class="tab-pane" id="target">
        <h1>you he</h1>
    </div>
    <div class="tab-pane" id="fines">
        <h1>you h</h1>
    </div>
    <div class="tab-pane" id="bills">
        <div class="row">
            <div class="col-sm-12">
        <?php try {
           echo PersonalBillsWidget::widget(['bills' => $info->bills]);
        } catch (Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
        } ?>
            </div>
        </div>
    </div>
</div>