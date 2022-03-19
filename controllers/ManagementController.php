<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 16:35
 */

namespace app\controllers;

use app\models\Cloud;
use app\models\MailSettings;
use app\models\utils\DbRestore;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class ManagementController extends Controller
{
    /**
     * @return array
     */
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'send-backup'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex(): string
    {
        // если переданы данные постом- это бекап базы данных
        if(Yii::$app->request->isPost){
            $restoreModel = new DbRestore();
            $restoreModel->file = UploadedFile::getInstance($restoreModel, 'file');
            $restoreModel->restore();
        }

        $mailSettings = MailSettings::getInstance();
        $dbRestore = new DbRestore();
        return $this->render('index', ['mailSettings' => $mailSettings, 'dbRestore' => $dbRestore]);
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSendBackup(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Cloud::sendBackup();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}