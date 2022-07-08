<?php

namespace app\widgets;

use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_membership;
use app\models\Table_additional_payed_membership;
use app\models\Table_payed_membership;
use app\models\Table_payment_bills;
use app\models\Table_tariffs_membership;
use app\models\TimeHandler;
use yii\base\Widget;
use yii\helpers\Html;

class PersonalBillsWidget extends Widget
{

    /**
     * @var Table_payment_bills[]
     */
    public array $bills;
    public string $content = '<table class="table table-condensed table-hover">';

    public function init(): void
    {
        $payedBills = [];
        $partialPayedBills = [];
        $unpaidBills = [];

        foreach ($this->bills as $bill) {
            if (!$bill->isPayed && CashHandler::toRubles($bill->payedSumm) === 0.0) {
                $unpaidBills[] = $bill;
            } else if (!$bill->isPayed && $bill->payedSumm < $bill->totalSumm) {
                $partialPayedBills[] = $bill;
            } else {
                $payedBills[] = $bill;
            }
        }
        $this->content .= "<tr><td>Всего счетов: </td><td>" . count($this->bills) . "</td></tr>";
        $this->content .= "<tr><td>Не оплачено: </td><td>" . count($unpaidBills) . "</td></tr>";
        $this->content .= "<tr><td>Оплачено частично: </td><td>" . count($partialPayedBills) . "</td></tr>";
        $this->content .= "<tr><td>Оплачено: </td><td>" . count($payedBills) . "</td></tr>";
        $this->content .= '</table>';

        $this->content .= '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
        $this->content .= '<div class="panel panel-default">
<div class="panel-heading" role="tab" id="headingUnpaid">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseUnpaid" aria-controls="collapseUnpaid">
          Неоплаченные счета
        </a>
      </h4>
    </div>
    <div id="collapseUnpaid" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingUnpaid">
      <div class="panel-body">';
        if(!empty($unpaidBills)){
            foreach ($unpaidBills as $bill) {

            }
        }
        else{
            $this->content .= '<h2 class="text-center text-info">Нет неоплаченных счетов</h2>';
        }
     $this->content .= '</div>
    </div>
</div>';
        $this->content .= '<div class="panel panel-default">
<div class="panel-heading" role="tab" id="headingPartialPayed">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapsePartialPayed" aria-controls="collapsePartialPayed">
          Частично оплаченные счета
        </a>
      </h4>
    </div>
    <div id="collapsePartialPayed" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingPartialPayed">
      <div class="panel-body">';
        if(!empty($partialPayedBills)){
            foreach ($partialPayedBills as $bill) {

            }
        }
        else{
            $this->content .= '<h2 class="text-center text-info">Нет частично оплаченных счетов</h2>';
        }
     $this->content .= '</div>
    </div></div>';
        $this->content .= '<div class="panel panel-default">
<div class="panel-heading" role="tab" id="headingPayed">
      <h4 class="panel-title">
        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapsePayed" aria-controls="collapsePartialPayed">
          Оплаченные счета
        </a>
      </h4>
    </div>
    <div id="collapsePayed" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingPayed">
      <div class="panel-body">';
        if(!empty($payedBills)){
            $this->content .= '<table class="table table-hover table-condensed">';
            foreach ($payedBills as $bill) {
                $this->content .= "<tr><td><a href='#' class='ajax-get-trigger' data-action='/personal-area/get-bill-info?id=$bill->id'>#$bill->id</a></td><td>" . TimeHandler::getDatetimeFromTimestamp($bill->creationTime) . "</td><td><b class='text-info'>" . CashHandler::toShortSmoothRubles($bill->totalSumm) . "</b></td><td><b class='text-success'>" . CashHandler::toShortSmoothRubles($bill->payedSumm) . "</b></td></tr>";
            }
            $this->content .= '</table>';
        }
        else{
            $this->content .= '<h2 class="text-center text-info">Нет оплаченных счетов</h2>';
        }
     $this->content .= '</div>
    </div></div>';
        $this->content .= '</div>';
    }

    public function run(): string
    {
        return Html::decode($this->content);
    }
}