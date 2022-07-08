<?php

namespace app\models\personal_area;

use app\models\Cottage;
use app\models\database\EmergencyCottageInfo;
use app\models\Table_cottages;
use app\models\Table_payment_bills;
use Yii;
use yii\base\Model;

class PersonalInfo extends Model
{

    public Table_cottages $cottage;
    public EmergencyCottageInfo $additionalCottageInfo;
    /**
     * @var Table_payment_bills[]
     */
    public array $bills;

    public function __construct($config = [])
    {
        parent::__construct($config);
        // получу актуальную информацию по участку
        $this->cottage = Cottage::getCottageByLiteral(Yii::$app->user->identity->username);
        $this->additionalCottageInfo = EmergencyCottageInfo::find()->where(['cottage_number' =>  $this->cottage->cottageNumber])->one();
        $this->bills = Table_payment_bills::find()->where(['cottageNumber' => $this->cottage->cottageNumber])->orderBy('id DESC')->all();
    }
}