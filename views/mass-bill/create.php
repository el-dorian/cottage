<?php

use app\assets\MassBillAsset;
use app\models\mass_bill\MassBill;
use kartik\depdrop\DepDrop;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

MassBillAsset::register($this);

/* @var $this View */
/* @var $model MassBill */

$this->title = 'Массовое выставление счетов';

$form = ActiveForm::begin(['id' => 'createMassBill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/mass-bill/create']]);

echo $form->field($model, 'type', [
    'template' =>
        '<div class="col-sm-4 with-margin">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->dropDownList(
        [
            'membership' => 'Членские',
            'target' => 'Целевые',
            'electricity' => 'Электроэнергия',
            'all_without_fines' => 'Все долги без пени',
            'all_with_fines' => 'Все долги с пени',
        ],
        ['prompt' => 'Выберите тип счёта']
    );

try {
    echo $form->field($model, 'period', [
        'options' => ['style' => 'display:none;', 'id' => 'periodDropdownContainer'],
        'template' =>
            '<div class="col-sm-4 with-margin">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])->widget(DepDrop::class, [
        'pluginOptions' => [
            'depends' => [Html::getInputId($model, 'type')],
            'placeholder' => 'Выберите...',
            'url' => Url::to(['/mass-bill/load-period'])
        ]
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
    echo $e->getTraceAsString();
}

echo $form->field($model, 'comment', ['template' =>
    '<div class="row with-margin"><div class="col-sm-4 with-margin">{label}</div><div class="col-sm-8">{input}{error}{hint}</div></div>'])
    ->textInput();

echo $form->field($model, 'sum', [
    'template' =>
        '<div class="row with-margin"><div class="col-sm-4 with-margin">{label}</div><div class="col-sm-8">{input}{error}{hint}</div></div>'])
    ->textInput(['type' => 'number', 'min' => '0', 'step' => '0.01'])
    ->hint('Сумма счёта в рублях. Если остаток долга по счёту меньше данной суммы- сумма будет выставлена по оставшейся задолженности. Если оставить поле пустым- будет учитываться полная сумма долга');

echo $form->field($model, 'payUpDate', ['template' =>
    '<div class="row with-margin"><div class="col-sm-4 with-margin">{label}</div><div class="col-sm-8">{input}{error}{hint}</div></div>'])
    ->input('date');

echo Html::submitButton('Сформировать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);

ActiveForm::end();

