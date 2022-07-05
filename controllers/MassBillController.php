<?php

namespace app\controllers;

use app\models\mass_bill\MassBill;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class MassBillController extends Controller
{

    /**
     * {@inheritdoc}
     */
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static function ($rule, $action) {
                    //return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'create',
                            'load-period',
                            'fill-list',
                            'confirm',
                            ],
                        'roles' => ['writer'
                        ],
                    ],
                ],
            ],
        ];
    }

    public function actionCreate(): array|string
    {
        $model = new MassBill();
        if(Yii::$app->request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            if(Yii::$app->request->isPost){
                $model->load(Yii::$app->request->post());
                return ActiveForm::validate($model);
            }
        }
        if(Yii::$app->request->isPost){
            $model->load(Yii::$app->request->post());
            if($model->validate()){
                // составлю предварительный список счетов
                $model->fillList();
                return $this->render('bills-list', ['model' => $model]);
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * @throws NotFoundHttpException
     */
    #[ArrayShape(['output' => "array", 'selected' => "string"])] public function actionLoadPeriod(): array
    {
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MassBill::getType();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionFillList(): string
    {
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            $model = new MassBill();
            $model->load(Yii::$app->request->post());
            if($model->validate()){
                $model->fillList();
                return $this->renderAjax('bills-list', ['model' => $model]);
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionConfirm(){
        $model = new MassBill();
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->load(Yii::$app->request->post());
            return ActiveForm::validate($model);
        }
        if(Yii::$app->request->isPost){
            $model->load(Yii::$app->request->post());
            if($model->validate()){
                $model->createBills();
                // верну страницу с распечаткой всех необходимых счетов
                return $this->renderPartial('print-invoices', ['model' => $model]);
            }
            Yii::$app->session->addFlash('danger', 'Не удалось выставить счета, попробуйте ещё раз');
            return $this->redirect('/mass-bill/create');
        }
        throw new NotFoundHttpException();
    }
}
