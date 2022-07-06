<?php

namespace app\models\personal_area;

use app\models\Cottage;
use app\models\User;
use app\models\utils\DbTransaction;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\base\Model;

class AddGardenerModel extends Model
{
    public ?string $cottageNumber = null;

    #[ArrayShape(['cottageNumber' => "string"])] public function attributeLabels(): array
    {
        return [
            'cottageNumber' => 'Номер участка'
        ];
    }

    public function rules(): array
    {
        return [
            ['cottageNumber', 'required'],
            ['cottageNumber', 'validateCottageNumber'],
        ];
    }

    public function validateCottageNumber($attribute): void
    {
        try {
            Cottage::getCottageByLiteral($this->$attribute);
        } catch (Exception) {
            $this->addError($attribute, "Не найден участок с номером {$this->$attribute}");
        }
        // проверю, не зарегистрирован ли уже участок
        if (User::findByUsername($this->$attribute) !== null) {
            $this->addError($attribute, "Участок с номером {$this->$attribute} уже зарегистрирован");
        }
    }

    /**
     * @throws Exception
     */
    public function register(): string
    {
        $transaction = new DbTransaction();
        $newUser = new User();
        $newUser->username = $this->cottageNumber;
        $password = Yii::$app->security->generateRandomString(6);
        $newUser->password_hash = Yii::$app->security->generatePasswordHash($password);
        $newUser->save();
        $auth = Yii::$app->authManager;
        if ($auth !== null) {
            $readerRole = $auth->getRole('beholder');
            $auth->assign($readerRole, $newUser->getId());
        }
        $transaction->commitTransaction();
        return $password;
    }
}