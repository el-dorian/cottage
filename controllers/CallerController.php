<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 08.12.2018
 * Time: 13:52
 */

namespace app\controllers;

use app\models\Cottage;
use app\models\database\Accruals_target;
use app\models\database\Calling;
use app\models\database\MailingSchedule;
use app\models\ExceptionWithStatus;
use app\models\GrammarHandler;
use app\models\Reminder;
use app\models\Report;
use app\models\Table_cottages;
use app\models\TimeHandler;
use app\models\utils\DbTransaction;
use app\models\utils\TotalDutyReport;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class CallerController extends Controller
{
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/login?redirect=' . urlencode($_SERVER['REQUEST_URI']), 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'current-calling',
                        ],
                        'roles' => ['reader'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws \app\models\ExceptionWithStatus
     */
    public function actionCurrentCalling(?string $cottage = null)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $item = Calling::findOne(['cottage' => $cottage]);
            if ($item !== null) {
                $item->state = Yii::$app->request->post('state');
                $item->save();
            }
            // count total statistics
            $notCalledCounter = 0;
            $notAvailableCounter = 0;
            $willComeCounter = 0;
            $willNotComeCounter = 0;
            $totalCalledCounter = 0;

            switch ($item->state) {
                case 'not_called' :
                    $stateColor = 'text-info';
                    break;
                case 'not_available' :
                    $stateColor = 'text-warning';
                    break;
                case 'will_come' :
                    $stateColor = 'text-success';
                    break;
                case 'will_not_come' :
                    $stateColor = 'text-danger';
                    break;
            }

            $state = Calling::find()->all();
            foreach ($state as $item) {
                switch ($item->state) {
                    case 'not_called' :
                        $notCalledCounter++;
                        break;
                    case 'not_available' :
                        $notAvailableCounter++;
                        $totalCalledCounter++;
                        break;
                    case 'will_come' :
                        $willComeCounter++;
                        $totalCalledCounter++;
                        break;
                    case 'will_not_come' :
                        $willNotComeCounter++;
                        $totalCalledCounter++;
                        break;
                }
            }
            return [
                'status' => 'success',
                'notCalled' => $notCalledCounter,
                'notAvailable' => $notAvailableCounter,
                'willCome' => $willComeCounter,
                'willNotCome' => $willNotComeCounter,
                'called' => $totalCalledCounter,
                'color' => $stateColor
            ];
        }
        if (empty(Calling::find()->all())) {
            // fill base
            $dbTransaction = new DbTransaction();
            $cottages = Cottage::getRegisteredList();
            foreach ($cottages as $cottage) {
                $phoneContent = '';
                $newCalling = new Calling();
                $newCalling->cottage = $cottage->getCottageNumber();
                if (!empty($cottage->cottageOwnerPhone)) {
                    $phoneContent .= "<div>Владелец: $cottage->cottageOwnerPersonals<a href=\"tel:$cottage->cottageOwnerPhone\">$cottage->cottageOwnerPhone</a></div>";
                }
                if (!empty($cottage->cottageContacterPhone)) {
                    $phoneContent .= "<div>Контактное лицо: $cottage->cottageContacterPersonals<a href=\"tel:$cottage->cottageContacterPhone\">$cottage->cottageContacterPhone</a></div>";
                }
                $newCalling->phonesData = $phoneContent;
                if (!empty($phoneContent)) {
                    $newCalling->save();
                }
            }
            $dbTransaction->commitTransaction();
        }
        $state = Calling::find()->all();
        return $this->render('calling', ['state' => $state]);
    }
}