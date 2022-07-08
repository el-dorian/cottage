<?php

namespace app\controllers;

use app\models\Cottage;
use app\models\database\MailingSchedule;
use app\models\Fix;
use app\models\personal_area\PersonalInfo;
use app\models\Table_payment_bills;
use app\models\TariffsKeeper;
use app\models\Utils;
use app\models\YaAuth;
use app\priv\Info;
use DateTime;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    #[ArrayShape(['access' => "array"])] public function behaviors():array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function(){
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'error',
                            'auth',
                            'mailing-schedule',
                            'access-error',
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'test',
                            'download-db',
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    #[ArrayShape(['error' => "string[]"])] public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }


    /**
     * Отображение списка участков
     * @return string|\yii\web\Response
     * @throws \Exception
     */
    public function actionIndex(): Response|string
    {
        if(Yii::$app->user->can('behold')){
            $this->layout = 'personal-area';
            $personalInfo = new PersonalInfo();
            return $this->render('personal-area', ['info' => $personalInfo]);
        }
        // запущу обновление данных
        Utils::startRefreshMainData();
        // Получу информацию о зарегистрированных участках
        $existedCottages = Cottage::getRegister();
        // Проверю заполненность тарифов на данный момент
        if(TariffsKeeper::checkFilling()){
            // проверю заполненность информации о долгах
            Utils::checkDebtFilling($existedCottages);
            return $this->render('index',['existedCottages' => $existedCottages]);
        }
        return $this->redirect('/tariffs/index', 301);
    }
    public function actionAuth(): Response|bool|string
    {
        if(empty($_SESSION['ya_auth'])){
            $model = new YaAuth();
            if (Yii::$app->request->isGet && !empty(Yii::$app->request->get('code'))) {
                $code = Yii::$app->request->get('code');
                if($model->authenticate($code)){
                    return $this->redirect('/site/auth', 301);
                }
            }
            else {
                return $this->render('auth', ['authModel' => $model]);
            }
        }
        else{
            return $this->redirect('/', 301);
        }
        return false;
    }

    /**
     *
     */
    public function actionTest(): void
    {
       /* $billsForSend = Table_payment_bills::find()->where(['>', "creationTime", 1653591397])->andWhere(['isPayed' => 0])->all();
        foreach ($billsForSend as $bill){
            MailingSchedule::addBankInvoiceSending($bill->id, false, true);
        }*/
        echo "done";
        //Fix::fixAccruals();

    }

    /**
     * @return string
     */
    public function actionMailingSchedule(): string
    {
        $waitingMessages = MailingSchedule::getWaiting();
        return $this->render('mailing-schedule', ['waiting' => $waitingMessages]);
    }

    /**
     * @throws \Exception
     */
    public function actionDownloadDb(): void
    {
        $path = Info::BACKUP_PATH . '/db.sql';
        $date = new DateTime();
        $d = $date->format('Y-m-d H:i:s');
        Yii::$app->response->sendFile($path, "Резервная копия базы данных СНТ $d.sql");
    }

    public function actionAccessError()
    {
        return $this->render('access-error');
    }
}
