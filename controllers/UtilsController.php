<?php


namespace app\controllers;


use app\models\database\Mail;
use app\models\ExceptionWithStatus;
use app\models\PenaltiesHandler;
use app\models\Utils;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class UtilsController extends Controller
{
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
                        'actions' => [
                            'addresses',
                            'count-penalties',
                            'fix',
                            'mail-delete',
                            'delete-target',
                            'fill-membership-accruals',
                            'fill-target-accruals',
                            'refresh-main-data',
                            'synchronize',
                            'backup-db',
                            'fix',
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionAddresses(): void
    {
        Utils::makeAddressesList();
        Yii::$app->response->xSendFile('post_addresses.xml');
    }

    /**
     * @throws Exception
     */
    public function actionCountPenalties(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return PenaltiesHandler::countPenalties();
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['status' => "int"])] public function actionFix(): array
    {
        // пофиксирую, если есть что
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::fillMembershipAccrualsForCottage(192);
        return ['status' => 1];
    }

    /**
     * @return array
     */
    public function actionMailDelete(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Mail::deleteMail();
    }

    /**
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['status' => "int", 'header' => "string", 'data' => "string"])] public function actionFillMembershipAccruals(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::fillMembershipAccruals();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['status' => "int", 'header' => "string", 'data' => "string"])] public function actionFillTargetAccruals(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::fillTargetAccruals();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    #[ArrayShape(['status' => "int", 'header' => "string", 'data' => "string"])] public function actionDeleteTarget(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::deleteTarget();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     */
    #[ArrayShape(['status' => "int", 'header' => "string", 'data' => "string"])] public function actionRefreshMainData(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::startRefreshMainData();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Данные обновлены'];
    }

    /**
     * @throws \JsonException
     */
    public function actionSynchronize($cottageNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Utils::sendInfoToApi($cottageNumber);
    }

    /**
     * Сохранение резервной копии базы данных
     *
     */
    public function actionBackupDb(): void
    {
        Utils::backupDatabase();
    }
}