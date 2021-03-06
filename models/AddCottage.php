<?php

namespace app\models;


use app\models\database\CottagesFastInfo;
use app\models\database\CottageSquareChanges;
use app\models\interfaces\CottageInterface;
use app\validators\CashValidator;
use app\validators\CheckCottageNoRegistred;
use app\validators\CheckCottageRegistred;
use app\validators\CheckMonthValidator;
use app\validators\CheckPhoneNumberValidator;
use app\validators\CheckQuarterValidator;
use app\validators\CheckTargetValidator;
use Exception;
use \Throwable;
use Yii;
use yii\base\Model;

class AddCottage extends Model
{
    public $cottageNumber; // номер участка.
    public $masterId; // номер участка.
    public $additional;
    public $haveRights; // наличие свидетельства о собственности
    public $cottageRegistrationInformation;// данные о кадастровом номере участка
    public $cottageOwnerPersonals; // личные данные владельца участка.
    public $cottageOwnerDescription; // личные данные владельца участка.
    public $cottageOwnerPhone; // контактный телефон владельца участка.
    public $cottageOwnerEmail; // адрес почты владельца участка.
    public $cottageSquare; // площадь участка.
    public $currentPowerData; // текущие показания счётчика электроэнергии.
    public $lastPayedMonth; // последний оплаченный месяц (электроэнергия).
    public $membershipPayFor; //  квартал, по который оплачены членские взносы.
    public $target; // год, по который оплачены членские взносы.
    public $targetPaysDuty; // сведения о задолженностях по целевым взносам
    public $deposit = 0; // Начальный депозит частка
    public $ownerAddressIndex = ''; // Индекс места проживания
    public $ownerAddressTown = ''; // Город проживания
    public $ownerAddressStreet = ''; // Улица проживания
    public $ownerAddressBuild = ''; // Номер дома
    public $ownerAddressFlat = ''; // Номер квартиры
    public $hasContacter = 0;
    public $cottageContacterPersonals; // личные данные владельца участка.
    public $cottageContacterPhone; // контактный телефон владельца участка.
    public $cottageContacterEmail; // адрес почты владельца участка.
    public $payerInfo; // имя плательщика для квитанции
    public $targetFilled = true;
    public $cottageRegisterData = false;

    public $passportData;
    public $cottageRightsData;

    public $existentTargets;
    public bool $fullChangeable = false;

    /**
     * @var Table_cottages|Table_additional_cottages
     */
    public CottageInterface $currentCondition; // сохранённая копия данных об участке
    private static $changedAttributes = ['cottageNumber', 'cottageSquare', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'cottageOwnerDescription', 'passportData', 'cottageRightsData', 'cottageRegistrationInformation'];
    private static $changedFullAttributes = ['cottageNumber', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageSquare', 'currentPowerData', 'membershipPayFor', 'deposit', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'targetPaysDuty'];
    private static $addChangedAttributes = ['masterId', 'cottageSquare', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'cottageOwnerDescription', 'passportData', 'cottageRightsData', 'cottageRegistrationInformation'];
    private static $addChangedFullAttributes = ['masterId', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageSquare', 'currentPowerData', 'membershipPayFor', 'deposit', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'targetPaysDuty'];

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return bool
     */
    public static function checkFullChangable($cottageInfo): bool
    {
        if (Cottage::isMain($cottageInfo))
            return !Table_payment_bills::find()->where(['cottageNumber' => $cottageInfo->cottageNumber])->count();
        return !Table_payment_bills_double::find()->where(['cottageNumber' => $cottageInfo->masterId])->count();
    }


    public function attributeLabels(): array
    {
        return [
            'cottageNumber' => 'Номер участка',
            'cottageOwnerPersonals' => 'Фамилия имя и отчество владельца',
            'cottageOwnerPhone' => 'Контактный номер телефона владельца',
            'cottageContacterPersonals' => 'Фамилия имя и отчество контактного лица',
            'cottageContacterPhone' => 'Номер телефона контактного лица',
            'cottageOwnerEmail' => 'Адрес электронной почты владельца',
            'haveRights' => 'Справка в наличии',
            'cottageRegisterData' => 'Наличие данных для реестра',
            'hasContacter' => 'Добавить контакт ',
            'cottageSquare' => 'Площадь участка',
            'currentPowerData' => 'Текущие показания счётчика электроэнергии',
            'membershipPayFor' => 'Месяц, по который оплачены членские взносы',
            'targetPayFor' => 'Месяц, по который оплачены целевые взносы',
            'payerInfo' => 'Имена плательщиков для квитанции',
        ];
    }

    public const SCENARIO_ADD = 'add';
    public const SCENARIO_CHANGE = 'change';
    public const SCENARIO_FULL_CHANGE = 'full-change';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_ADD => ['cottageNumber', 'haveRights', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageSquare', 'currentPowerData', 'lastPayedMonth', 'membershipPayFor', 'target', 'deposit', 'ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat', 'hasContacter', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'targetFilled'],
            self::SCENARIO_FULL_CHANGE => ['cottageNumber', 'masterId', 'fullChangeable', 'haveRights', 'cottageOwnerPersonals', 'cottageOwnerDescription', 'cottageOwnerPhone', 'cottageOwnerEmail', 'cottageSquare', 'currentPowerData', 'lastPayedMonth', 'membershipPayFor', 'target', 'deposit', 'ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat', 'hasContacter', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'targetFilled', 'cottageRegisterData', 'payerInfo'],
            self::SCENARIO_CHANGE => ['cottageNumber', 'cottageSquare', 'masterId', 'fullChangeable', 'haveRights', 'cottageOwnerPersonals', 'cottageRegisterData', 'cottageOwnerPhone', 'cottageOwnerDescription', 'cottageOwnerEmail', 'hasContacter', 'cottageContacterPersonals', 'cottageContacterPhone', 'cottageContacterEmail', 'ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat', 'passportData', 'cottageRightsData', 'cottageRegistrationInformation', 'payerInfo'],
        ];
    }

    public function rules(): array
    {
        return [
            [['cottageNumber', 'cottageOwnerPersonals', 'cottageSquare', 'currentPowerData', 'membershipPayFor', 'deposit', 'targetFilled'], 'required', 'on' => self::SCENARIO_ADD],
            [['cottageNumber', 'cottageOwnerPersonals'], 'required', 'on' => self::SCENARIO_CHANGE],
            ['cottageNumber', CheckCottageNoRegistred::class, 'on' => [self::SCENARIO_CHANGE, self::SCENARIO_FULL_CHANGE]],
            ['masterId', CheckCottageNoRegistred::class, 'on' => [self::SCENARIO_CHANGE, self::SCENARIO_FULL_CHANGE]],
            [['cottageContacterPersonals'], 'required', 'when' => function () {
                return $this->hasContacter;
            }, 'whenClient' => "function () {return $('input#addcottage-hascontacter').prop('checked');}"],
            ['cottageNumber', 'integer', 'min' => 1, 'max' => 300],
            [['haveRights', 'hasContacter', 'cottageRegisterData'], 'boolean'],
            [['cottageOwnerPersonals', 'cottageContacterPersonals'], 'match', 'pattern' => '/^[ёа-я- ]*$/iu', 'message' => 'Проверьте правильность данных. Разрешены буквы, тире и пробел!'],
            [['cottageOwnerPhone', 'cottageContacterPhone'], CheckPhoneNumberValidator::class],
            [['cottageOwnerEmail', 'cottageContacterEmail'], 'email'],
            ['cottageSquare', 'integer', 'min' => 1, 'max' => 1000],
            ['cottageNumber', CheckCottageRegistred::class, 'on' => self::SCENARIO_ADD],
            ['currentPowerData', 'integer', 'min' => 0, 'max' => 9999999999],
            ['membershipPayFor', CheckQuarterValidator::class],
            ['lastPayedMonth', CheckMonthValidator::class],
            ['deposit', CashValidator::class],
            ['targetFilled', CheckTargetValidator::class],
            [['ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat'], 'string', 'max' => 200],
            [['cottageOwnerDescription'], 'string', 'max' => 500],
        ];
    }


    /**
     * @return bool
     * @throws Throwable
     * @throws Exception
     */
    public function save(): bool
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $data = null;
            // если добавляем данные- создаю участок
            if ($this->scenario === 'add') {
                $data = new Table_cottages;
                $data->cottageNumber = $this->cottageNumber;
                $data->cottageOwnerPersonals = GrammarHandler::clearWhitespaces($this->cottageOwnerPersonals);
                $data->cottageOwnerPhone = $this->cottageOwnerPhone;
                $data->cottageOwnerEmail = $this->cottageOwnerEmail;
                $data->cottageSquare = $this->cottageSquare;
                $data->membershipPayFor = $this->membershipPayFor;
                $data->currentPowerData = $this->currentPowerData;
                $data->deposit = $this->deposit;
                $data->cottageOwnerDescription = GrammarHandler::clearWhitespaces($this->cottageOwnerDescription);
                $data->singleDebt = 0;
                $data->singlePaysDuty = '<singlePayments/>';
                // если указано контактное лицо- вношу его данные в информацию
                if ($this->hasContacter) {
                    $data->cottageContacterPersonals = $this->cottageContacterPersonals;
                    $data->cottageContacterPhone = $this->cottageContacterPhone;
                    $data->cottageContacterEmail = $this->cottageContacterEmail;
                } else {
                    $data->cottageContacterPersonals = '';
                    $data->cottageContacterPhone = '';
                    $data->cottageContacterEmail = '';
                }
                // добавлю сведения об адресе
                $data->cottageOwnerAddress = "$this->ownerAddressIndex & $this->ownerAddressTown & $this->ownerAddressStreet & $this->ownerAddressBuild & $this->ownerAddressFlat";
                $data->cottageHaveRights = (bool)$this->haveRights;
                // буду считать, что электроэнергия полностью оплачена за позапрошлый месяц- все долги оплачиваются через разовый платеж
                if (empty($this->lastPayedMonth)) {
                    $prev = TimeHandler::getTwoMonthAgo();
                } else {
                    $prev = $this->lastPayedMonth;
                }
                $data->powerPayFor = $prev;
                // проверю данные по целевым платежам.
                $dutyInfo = TargetHandler::calculateDuty($this->target, $data);
                $data->targetDebt = $dutyInfo['totalDuty'];
                $data->targetPaysDuty = $dutyInfo['dutyDetails'];
                $data->powerDebt = 0;
                $data->save();
                $session = Yii::$app->session;
                // добавляю в таблицу данных учёта электроэнергии новую запись, с которой будет начинаться отчёт
                $power = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD, 'attributes' => ['cottageNumber' => $this->cottageNumber, 'month' => $prev, 'newPowerData' => $this->currentPowerData]]);
                $power->insert();
                $membershipDifference = MembershipHandler::getTariffs(['start' => $data->membershipPayFor, 'finish' => TimeHandler::getCurrentQuarter()]);
                if (!empty($membershipDifference)) {
                    foreach ($membershipDifference as $key => $item) {
                        MembershipHandler::recalculateMembership($key);
                    }
                }
                $targets = TargetHandler::getCurrentRates();
                if ($targets != null) {
                    foreach ($targets as $key => $value) {
                        TargetHandler::recalculateTarget($key);
                    }
                }
                // создам строку с быстрыми данными
                $fastData = new CottagesFastInfo();
                $fastData->cottage_number = $this->cottageNumber;
                $fastData->save();
                $session->addFlash('success', "Успешно добавлен участок №{$data->cottageNumber}. <a href='/show-cottage/{$data->cottageNumber}' class='btn btn-info'>Просмотреть информацию по участку.</a> ");
                $transaction->commit();
                return true;
            }
            if ($this->scenario === 'change' || $this->scenario === 'change-add' || $this->scenario === self::SCENARIO_FULL_CHANGE) {

                if ($this->cottageSquare !== $this->currentCondition->cottageSquare) {
                    // обработаю изменения в связи со сменой площади
                    CottageSquareChanges::changeSquare($this->currentCondition, $this->cottageSquare);
                }
                // заполню форму безопасными данными
                $this->currentCondition->cottageOwnerPersonals = $this->cottageOwnerPersonals;
                $this->currentCondition->cottageSquare = $this->cottageSquare;
                $this->currentCondition->cottageRegistrationInformation = $this->cottageRegistrationInformation;

                $this->currentCondition->passportData = $this->passportData;
                $this->currentCondition->cottageRightsData = $this->cottageRightsData;
                $this->currentCondition->cottageRegisterData = $this->cottageRegisterData;

                $this->currentCondition->cottageOwnerEmail = $this->cottageOwnerEmail;
                $this->currentCondition->cottageOwnerPhone = $this->cottageOwnerPhone;
                $this->currentCondition->cottageOwnerAddress = "$this->ownerAddressIndex & $this->ownerAddressTown & $this->ownerAddressStreet & $this->ownerAddressBuild & $this->ownerAddressFlat";
                $this->currentCondition->cottageOwnerDescription = $this->cottageOwnerDescription;
                $this->currentCondition->bill_payers = $this->payerInfo;
                if (!empty($this->haveRights) && $this->haveRights === '1') {
                    $this->currentCondition->cottageHaveRights = 1;
                } else {
                    $this->currentCondition->cottageHaveRights = 0;
                }
                if ($this->hasContacter === '1') {
                    $this->currentCondition->cottageContacterPersonals = $this->cottageContacterPersonals;
                    $this->currentCondition->cottageContacterEmail = $this->cottageContacterEmail;
                    $this->currentCondition->cottageContacterPhone = $this->cottageContacterPhone;
                } else {
                    $this->currentCondition->cottageContacterPersonals = '';
                    $this->currentCondition->cottageContacterEmail = '';
                    $this->currentCondition->cottageContacterPhone = '';
                }
            }
            if ($this->scenario === self::SCENARIO_FULL_CHANGE) {
                if ($this->additional) {
                    if ($this->currentCondition->isPower) {
                        if ($this->currentPowerData !== $this->currentCondition->currentPowerData) {
                            $clearData = $this->currentPowerData;
                            $this->currentCondition->currentPowerData = $clearData;
                            $data = Table_additional_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->all();
                            foreach ($data as $item) {
                                $item->oldPowerData = $clearData;
                                $item->newPowerData = $clearData;
                                $item->save();
                            }
                        }
                        if ($this->lastPayedMonth !== $this->currentCondition->powerPayFor) {
                            $this->currentCondition->powerPayFor = $this->lastPayedMonth;
                            // удалю все предыдущие показания по этому участку
                            $data = Table_additional_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->all();
                            foreach ($data as $item) {
                                $item->delete();
                            }
                            $startMonth = new Table_additional_power_months();
                            $startMonth->cottageNumber = $this->cottageNumber;
                            $startMonth->month = $this->currentCondition->powerPayFor;
                            $startMonth->fillingDate = time();
                            $startMonth->oldPowerData = $this->currentPowerData;
                            $startMonth->newPowerData = $this->currentPowerData;
                            $startMonth->searchTimestamp = TimeHandler::getMonthTimestamp($this->currentCondition->powerPayFor);
                            $startMonth->payed = 'yes';
                            $startMonth->save();
                        }
                    }
                } else {
                    if ($this->currentPowerData !== $this->currentCondition->currentPowerData) {
                        $clearData = $this->currentPowerData;
                        $this->currentCondition->currentPowerData = $clearData;
                        $data = Table_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->all();
                        foreach ($data as $item) {
                            $item->oldPowerData = $clearData;
                            $item->newPowerData = $clearData;
                            $item->save();
                        }
                    }
                    if ($this->lastPayedMonth !== $this->currentCondition->powerPayFor) {
                        $this->currentCondition->powerPayFor = $this->lastPayedMonth;
                        // удалю все предыдущие показания по этому участку
                        $data = Table_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->all();
                        foreach ($data as $item) {
                            $item->delete();
                        }
                        $startMonth = new Table_power_months();
                        $startMonth->cottageNumber = $this->cottageNumber;
                        $startMonth->month = $this->currentCondition->powerPayFor;
                        $startMonth->fillingDate = time();
                        $startMonth->oldPowerData = $this->currentPowerData;
                        $startMonth->newPowerData = $this->currentPowerData;
                        $startMonth->searchTimestamp = TimeHandler::getMonthTimestamp($this->currentCondition->powerPayFor);
                        $startMonth->payed = 'yes';
                        $startMonth->save();
                    }
                }

                if ($this->membershipPayFor !== $this->currentCondition->membershipPayFor) {
                    $this->currentCondition->membershipPayFor = $this->membershipPayFor;
                }
                if ($this->deposit !== $this->currentCondition->deposit) {
                    $this->currentCondition->deposit = $this->deposit;
                }
                // теперь самое сложное- ищу изменения в оплате целевых платежей
                // просто пересохраню начисто
                if ($this->additional) {
                    if ($this->currentCondition->isTarget) {
                        $targetDebt = 0;
                        $content = '<targets>';
                        if (!empty($this->target)) {
                            $this->fillTargets();
                            foreach ($this->target as $key => $value) {
                                $full = Calculator::countFixedFloat($this->existentTargets[$key]['fixed'], $this->existentTargets[$key]['float'], $this->cottageSquare);
                                // сравню тариф и сведения о нём
                                if ($value['payed-of'] === 'no-payed') {
                                    // Добавлю данный взнос как полностью неоплаченный
                                    $content .= "<target payed='0' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                                    $targetDebt += $full;
                                } elseif ($value['payed-of'] === 'partial') {
                                    $unpayed = CashHandler::rublesMath($full - $value['payed-summ']);
                                    $content .= "<target payed='{$value['payed-summ']}' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                                    $targetDebt += $unpayed;
                                }
                            }
                        }
                        $content .= '</targets>';
                        $this->currentCondition->targetDebt = $targetDebt;
                        $this->currentCondition->targetPaysDuty = $content;
                    }
                } else {
                    $targetDebt = 0;
                    $content = '<targets>';
                    if (!empty($this->target)) {
                        $this->fillTargets();
                        foreach ($this->target as $key => $value) {
                            $full = Calculator::countFixedFloat($this->existentTargets[$key]['fixed'], $this->existentTargets[$key]['float'], $this->cottageSquare);
                            // сравню тариф и сведения о нём
                            if ($value['payed-of'] === 'no-payed') {
                                // Добавлю данный взнос как полностью неоплаченный
                                $content .= "<target payed='0' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                                $targetDebt += $full;
                            } elseif ($value['payed-of'] === 'partial') {
                                $unpayed = CashHandler::rublesMath($full - $value['payed-summ']);
                                $content .= "<target payed='{$value['payed-summ']}' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                                $targetDebt += $unpayed;
                            }
                        }
                    }
                    $content .= '</targets>';
                    $this->currentCondition->targetDebt = $targetDebt;
                    $this->currentCondition->targetPaysDuty = $content;
                }
            }

            $this->currentCondition->save();
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return bool
     */
    public function fill($cottageInfo): bool
    {
        $this->fillTargets();

        // проверю возможность изменения данных, связанных с платежами
        if (Cottage::isMain($cottageInfo)) {
            $this->fullChangeable = !Table_payment_bills::find()->where(['cottageNumber' => $cottageInfo->cottageNumber])->count();

            if ($this->fullChangeable) {
                foreach (self::$changedFullAttributes as $attribute) {
                    $this->$attribute = $cottageInfo->$attribute;
                    $this->lastPayedMonth = $cottageInfo->powerPayFor;
                }
            } else {
                foreach (self::$changedAttributes as $attribute) {
                    $this->$attribute = $cottageInfo->$attribute;
                }
            }
        } else {
            $this->fullChangeable = !Table_payment_bills_double::find()->where(['cottageNumber' => $cottageInfo->masterId])->count();

            if ($this->fullChangeable) {
                foreach (self::$addChangedFullAttributes as $attribute) {
                    $this->$attribute = $cottageInfo->$attribute;
                    $this->lastPayedMonth = $cottageInfo->powerPayFor;
                }
            } else {
                foreach (self::$addChangedAttributes as $attribute) {
                    $this->$attribute = $cottageInfo->$attribute;
                }
            }
        }


        $this->payerInfo = $cottageInfo->bill_payers;

        $this->haveRights = $cottageInfo->cottageHaveRights;
        $this->cottageRegisterData = $cottageInfo->cottageRegisterData;
        $this->ownerAddressTown = $cottageInfo->cottageOwnerAddress;
        if (!empty($cottageInfo->cottageContacterPersonals)) {
            $this->hasContacter = true;
            $this->cottageContacterPersonals = $cottageInfo->cottageContacterPersonals;
            $this->cottageContacterEmail = $cottageInfo->cottageContacterEmail;
            $this->cottageContacterPhone = $cottageInfo->cottageContacterPhone;
        }
        // попробую разобрать данные адреса вдадельца
        $addressArray = explode('&', $cottageInfo->cottageOwnerAddress);
        if (count($addressArray) === 5) {
            $this->ownerAddressIndex = $addressArray[0];
            $this->ownerAddressTown = $addressArray[1];
            $this->ownerAddressStreet = $addressArray[2];
            $this->ownerAddressBuild = $addressArray[3];
            $this->ownerAddressFlat = $addressArray[4];
        } else {
            $this->ownerAddressTown = GrammarHandler::clearAddress($cottageInfo->cottageOwnerAddress);
        }
        return true;
    }

    public function fillTargets()
    {
        $this->existentTargets = Table_tariffs_target::find()->orderBy('year')->all();
        $targetsList = [];
        foreach ($this->existentTargets as $value) {
            $targetsList[$value->year] = ['fixed' => $value->fixed_part, 'float' => $value->float_part, 'description' => $value->description];
        }
        $this->existentTargets = $targetsList;
    }
}