<?php

namespace app\models\mass_bill;

use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\database\MailingSchedule;
use app\models\ExceptionWithStatus;
use app\models\Table_payment_bills;
use app\models\Table_payment_bills_double;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_power;
use app\models\Table_tariffs_target;
use app\models\TimeHandler;
use app\models\utils\DbTransaction;
use app\validators\CashValidator;
use JetBrains\PhpStorm\ArrayShape;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;

class MassBill extends Model
{
    public ?string $type = null;
    public ?string $period = null;
    public ?string $comment = null;
    public ?string $sum = null;
    public ?string $payUpDate = null;
    public array $bills = [];
    public array $accruedBills = [];

    #[ArrayShape(['output' => "array", 'selected' => "string"])] public static function getType(): array
    {
        $out = [];
        if (!empty($_POST['depdrop_parents'])) {
            $type = $_POST['depdrop_parents'][0];
            switch ($type) {
                case 'membership' :
                    // верну заполненные тарифы по членским платежам
                    $tariffs = Table_tariffs_membership::find()->orderBy('quarter DESC')->all();
                    foreach ($tariffs as $tariff) {
                        $out[] = ['id' => $tariff->quarter, 'name' => TimeHandler::getFullFromShortQuarter($tariff->quarter)];
                    }
                    break;
                case 'target' :
                    // верну заполненные тарифы по членским платежам
                    $tariffs = Table_tariffs_target::find()->orderBy('year DESC')->all();
                    foreach ($tariffs as $tariff) {
                        $out[] = ['id' => $tariff->year, 'name' => "$tariff->year год"];
                    }
                    break;
                case 'electricity' :
                    // верну заполненные тарифы по членским платежам
                    $tariffs = Table_tariffs_power::find()->orderBy('targetMonth DESC')->all();
                    foreach ($tariffs as $tariff) {
                        $out[] = ['id' => $tariff->targetMonth, 'name' => TimeHandler::getFullFromShotMonth($tariff->targetMonth)];
                    }
                    break;
            }
        }
        return ['output' => $out, 'selected' => ''];
    }

    #[ArrayShape(['type' => "string", 'period' => "string", 'comment' => "string", 'sum' => "string", 'payUpDate' => "string", 'bills' => "string"])] public function attributeLabels(): array
    {
        return [
            'type' => 'Выберите тип счёта',
            'period' => 'Выберите период',
            'comment' => 'Комментарий к платежу',
            'sum' => 'Максимальная сумма платежа',
            'payUpDate' => 'Срок оплаты счёта',
            'bills' => 'Список счетов',
        ];
    }

    public function rules(): array
    {
        return
            [
                ['comment', 'string', 'skipOnEmpty' => true],
                ['payUpDate', 'string', 'skipOnEmpty' => true],
                ['bills', 'validateBills', 'skipOnEmpty' => true],
                ['sum', CashValidator::class, 'skipOnEmpty' => true],
                [
                    'type', 'required',
                    'whenClient' => "function(attribute, value) {
                        let typeDropdown = $('select#" . Html::getInputId($this, 'is_payer') . "');
                         typeDropdown.on('change.inspect', function(){
                            console.log($(this).val());
                      });
                  }"
                ],
                [
                    'period', 'required',
                    'when' => function ($model) {
                        return $model->type === 'target' || $model->type === 'membership' || $model->type === 'electricity';
                    },
                    'whenClient' => "function(attribute, value) {
                      let typeDropdown = $('select#" . Html::getInputId($this, 'type') . "');
                      let periodDropdownContainer = $('div#periodDropdownContainer');
                      console.log(typeDropdown.val());
                      console.log(periodDropdownContainer);
                      
                      if(typeDropdown.val() === 'membership' || typeDropdown.val() === 'target' || typeDropdown.val() === 'electricity'){
                       periodDropdownContainer.show();     
                      }
                      else{
                      periodDropdownContainer.hide();  
                      }
                  }"
                ]
            ];
    }

    public function validateBills($attribute): void
    {
        $data = $this->$attribute;
        if (is_string($data)) {
            $data = Json::encode($data);
        }
        foreach ($data as $key => $value) {
            $period = $value['period'];
            $type = $value['type'];
            $sum = $value['sum'];
            $cottage = $value['cottage'];
            $cottageItem = Cottage::getCottageByLiteral($cottage);
            if ($cottageItem === null) {
                $target = "{$attribute}[$key][cottage]";
                $this->addError($target, "Не найден участок с номером $cottage");
                return;
            }
            switch ($type) {
                case 'Членские':
                    $accrual = Accruals_membership::findOne(['cottage_number' => $cottage, 'quarter' => $period]);
                    break;
                case 'Целевые':
                    $accrual = Accruals_target::findOne(['cottage_number' => $cottage, 'year' => $period]);
                    break;
                default:
                    $accrual = null;
            }
            if ($accrual === null) {
                $target = "{$attribute}[$key][period]";
                $this->addError($target, "Не найдены начисления участку с номером $cottage за $period");
                return;
            }
            $sumToPay = CashHandler::toRubles($accrual->countAmount() - $accrual->countPayed());
            if (CashHandler::toRubles($sum) > $sumToPay) {
                $target = "{$attribute}[$key][sum]";
                $this->addError($target, "Сумма больше задолженности участка по счёту ($sumToPay)");
            }
        }
    }

    public function fillList(): void
    {
        switch ($this->type) {
            case 'membership' :
                $this->createMembershipBills();
                break;
            case 'target' :
                $this->createTargetBills();
        }
    }

    private function createMembershipBills(): void
    {
        $accruals = Accruals_membership::find()->where(['quarter' => $this->period])->all();
        foreach ($accruals as $accrual) {
            if ($accrual->cottage_number > 0 && $accrual->countPayed() < $accrual->countAmount()) {
                $sumToPay = CashHandler::toRubles($accrual->countAmount() - $accrual->countPayed());
                if (!empty($this->sum) && CashHandler::toRubles($this->sum) < $sumToPay) {
                    $sumToPay = CashHandler::toRubles($this->sum);
                }
                $hasMail = Cottage::hasMail(Cottage::getCottageByLiteral($accrual->cottage_number));
                $this->bills[] = [
                    'type' => 'Членские',
                    'period' => $this->period,
                    'sum' => $sumToPay,
                    'cottage' => $accrual->cottage_number,
                    'hasMail' => $hasMail,
                    'printInvoice' => !$hasMail
                ];
            }
        }
    }

    private function createTargetBills(): void
    {
        $accruals = Accruals_target::find()->where(['year' => $this->period])->all();
        foreach ($accruals as $accrual) {
            $sumToPay = CashHandler::toRubles($accrual->countAmount() - $accrual->countPayed());
            if (!empty($this->sum) && CashHandler::toRubles($this->sum) < $sumToPay) {
                $sumToPay = CashHandler::toRubles($this->sum);
            }
            $hasMail = Cottage::hasMail(Cottage::getCottageByLiteral($accrual->cottage_number));
            if ($accrual->countPayed() < $accrual->countAmount()) {
                $this->bills[] = [
                    'type' => 'Целевые',
                    'period' => $this->period,
                    'sum' => $sumToPay,
                    'cottage' => $accrual->cottage_number,
                    'hasMail' => $hasMail,
                    'printInvoice' => !$hasMail
                ];
            }
        }
    }

    /**
     * @throws \app\models\ExceptionWithStatus
     */
    public function createBills()
    {
        $dbTransaction = new DbTransaction();
        foreach ($this->bills as $bill) {
            $newBill = null;
            $cottageNumber = $bill['cottage'];
            $sum = $bill['sum'];
            $period = $bill['period'];
            $cottage = Cottage::getCottageByLiteral($cottageNumber);
            if ($cottage->isMain()) {
                $entity = null;
                switch ($bill['type']) {
                    case "Членские":
                        $accrual = Accruals_membership::findOne(['cottage_number' => $cottageNumber, 'quarter' => $period]);
                        if ($accrual === null) {
                            throw new ExceptionWithStatus("Не найдены начисления по участку $cottageNumber по членским за $period");
                        }
                        $entity = "<membership cost='$sum'><quarter date='$accrual->quarter' summ='$sum' square='$accrual->counted_square' float-cost='" . $accrual->countFloatCost() . "' float='$accrual->square_part' fixed='$accrual->fixed_part' prepayed='" . $accrual->countPayed() . "'/></membership>";
                        break;
                    case "Целевые":
                        $accrual = Accruals_target::findOne(['cottage_number' => $cottageNumber, 'year' => $period]);
                        if ($accrual === null) {
                            throw new ExceptionWithStatus("Не найдены начисления по участку $cottageNumber по целевым за $period");
                        }
                        $entity = "<target cost='$sum'><pay year='$accrual->year' summ='$sum'  total-summ='" . $accrual->countAmount() . "' square='$accrual->counted_square' float-cost='" . $accrual->countFloatCost() . "' float='$accrual->square_part' fixed='$accrual->fixed_part'  payed-before='" . $accrual->countPayed() . "' left-pay='" . $accrual->countLeftPayed() . "'/></target>";
                        break;
                }
                $newBill = new Table_payment_bills();
                $newBill->cottageNumber = $cottage->getBaseCottageNumber();
                $content = "<payment summ='{$sum}'>{$entity}</payment>";
                $newBill->bill_content = $content;
                $newBill->isPayed = 0;
                $newBill->creationTime = time();
                $newBill->totalSumm = $sum;
                if ($cottage->deposit > 0) {
                    if ($cottage->deposit < $newBill->totalSumm) {
                        $newBill->depositUsed = $cottage->deposit;
                        $cottage->deposit = 0;
                    } else {
                        $newBill->depositUsed = $newBill->totalSumm;
                        $cottage->deposit = CashHandler::toRubles($cottage->deposit - $newBill->totalSumm);
                    }
                    $cottage->save();
                } else {
                    $newBill->depositUsed = 0;
                }
                if (!empty($cottage->bill_payers)) {
                    $newBill->payer_personals = $cottage->bill_payers;
                } else {
                    $newBill->payer_personals = $cottage->cottageOwnerPersonals;
                }
                $newBill->comment = $this->comment;
                if (!empty($this->payUpDate)) {
                    $newBill->payUpDate = TimeHandler::getCustomTimestamp($this->payUpDate);
                }
                if ($bill['hasMail']) {
                    $newBill->isMessageSend = 1;
                }
                if ($bill['printInvoice']) {
                    $newBill->isInvoicePrinted = 1;
                }
                $newBill->save();
                $this->accruedBills[] = $newBill;

            } else {
                $entity = null;
                switch ($bill['type']) {
                    case "Членские":
                        $accrual = Accruals_membership::findOne(['cottage_number' => $cottageNumber, 'quarter' => $period]);
                        if ($accrual === null) {
                            throw new ExceptionWithStatus("Не найдены начисления по участку $cottageNumber по членским за $period");
                        }
                        $entity = "<additional_membership cost='$sum'><quarter date='$accrual->quarter' summ='$sum' square='$accrual->counted_square' float-cost='" . $accrual->countFloatCost() . "' float='$accrual->square_part' fixed='$accrual->fixed_part' prepayed='" . $accrual->countPayed() . "'/></additional_membership>";
                        break;
                    case "Целевые":
                        $accrual = Accruals_target::findOne(['cottage_number' => $cottageNumber, 'year' => $period]);
                        if ($accrual === null) {
                            throw new ExceptionWithStatus("Не найдены начисления по участку $cottageNumber по целевым за $period");
                        }
                        $entity = "<additional_target cost='$sum'><pay year='$accrual->year' summ='$sum' total-summ='" . $accrual->countAmount() . "' square='$accrual->counted_square' float-cost='" . $accrual->countFloatCost() . "' float='$accrual->square_part' fixed='$accrual->fixed_part' payed-before='" . $accrual->countPayed() . "' left-pay='" . $accrual->countLeftPayed() . "'/></additional_target>";
                        break;
                }
                if ($cottage->hasDifferentOwner) {
                    $newBill = new Table_payment_bills_double();
                    $newBill->cottageNumber = $cottage->getBaseCottageNumber();
                    $content = "<payment summ='{$sum}'>{$entity}</payment>";
                    $newBill->bill_content = $content;
                    $newBill->isPayed = 0;
                    $newBill->creationTime = time();
                    $newBill->totalSumm = $sum;
                    if ($cottage->deposit > 0) {
                        if ($cottage->deposit < $newBill->totalSumm) {
                            $newBill->depositUsed = $cottage->deposit;
                            $cottage->deposit = 0;
                        } else {
                            $newBill->depositUsed = $newBill->totalSumm;
                            $cottage->deposit = CashHandler::toRubles($cottage->deposit - $newBill->totalSumm);
                        }
                        $cottage->save();
                    } else {
                        $newBill->depositUsed = 0;
                    }
                    if (!empty($cottage->bill_payers)) {
                        $newBill->payer_personals = $cottage->bill_payers;
                    } else {
                        $newBill->payer_personals = $cottage->cottageOwnerPersonals;
                    }
                    $newBill->comment = $this->comment;
                    if (!empty($this->payUpDate)) {
                        $newBill->payUpDate = TimeHandler::getCustomTimestamp($this->payUpDate);
                    }
                } else {
                    $newBill = new Table_payment_bills();
                    $masterCottage = Cottage::getCottageByLiteral($cottage->getBaseCottageNumber());
                    $newBill->cottageNumber = $cottage->getBaseCottageNumber();
                    $content = "<payment summ='{$sum}'>{$entity}</payment>";
                    $newBill->bill_content = $content;
                    $newBill->isPayed = 0;
                    $newBill->creationTime = time();
                    $newBill->totalSumm = $sum;
                    if ($masterCottage->deposit > 0) {
                        if ($masterCottage->deposit < $newBill->totalSumm) {
                            $newBill->depositUsed = $masterCottage->deposit;
                            $masterCottage->deposit = 0;
                        } else {
                            $newBill->depositUsed = $newBill->totalSumm;
                            $masterCottage->deposit = CashHandler::toRubles($masterCottage->deposit - $newBill->totalSumm);
                        }
                        $masterCottage->save();
                    } else {
                        $newBill->depositUsed = 0;
                    }
                    $newBill->comment = $this->comment;
                    if (!empty($this->payUpDate)) {
                        $newBill->payUpDate = TimeHandler::getCustomTimestamp($this->payUpDate);
                    }
                    if (!empty($masterCottage->bill_payers)) {
                        $newBill->payer_personals = $masterCottage->bill_payers;
                    } else {
                        $newBill->payer_personals = $masterCottage->cottageOwnerPersonals;
                    }

                }
                if ($bill['hasMail']) {
                    $newBill->isMessageSend = 1;
                }
                if ($bill['printInvoice']) {
                    $newBill->isInvoicePrinted = 1;
                }
                $newBill->save();
                $this->accruedBills[] = $newBill;
            }
            if ($newBill !== null) {
                //(new FirebaseHandler())->sendNewBillNotification($bill);
                if ($newBill->isMessageSend === 1) {
                    // добавлю сообщения в очередь отправки
                    MailingSchedule::addBankInvoiceSending($newBill->id, $newBill instanceof Table_payment_bills_double, true);
                }
            }
        }
        $dbTransaction->commitTransaction();
    }
}