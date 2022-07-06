<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 22.10.2018
 * Time: 16:48
 */

namespace app\controllers;

use app\models\personal_area\AddGardenerModel;
use app\models\selections\AjaxRequestStatus;
use app\models\User;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;


class PersonalAreaController extends Controller
{

    #[ArrayShape(['access' => "array"])] public function behaviors():array
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
                            'add-gardener',
                            'change-pass',
                            'delete',
                        ],
                        'roles' => ['manager'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionAddGardener(): Response|array
    {
        $model = new AddGardenerModel();
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->load(Yii::$app->request->post());
            return ActiveForm::validate($model);
        }
        if(Yii::$app->request->isPost){
            $model->load(Yii::$app->request->post());
            if($model->validate()){
                $password = $model->register();
                Yii::$app->session->addFlash("success", "Добавлен. Пароль- $password");
            }
            else{
                Yii::$app->session->addFlash("danger", "Не удалось добавить.");
            }
            return $this->redirect($_SERVER['HTTP_REFERER'], 301);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionChangePass(string $id): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                $newPass = User::changePassword($id);
                return AjaxRequestStatus::successWithMessage("Новый пароль: $newPass");
            } catch (Exception $e) {
                return AjaxRequestStatus::failed($e->getMessage());
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionDelete(string $id): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                $newPass = User::deleteBeholder($id);
                return AjaxRequestStatus::success();
            } catch (Exception $e) {
                return AjaxRequestStatus::failed($e->getMessage());
            }
        }
        throw new NotFoundHttpException();
    }
}