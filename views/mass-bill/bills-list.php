<?php

use app\assets\MassBillAsset;
use app\models\CashHandler;
use app\models\mass_bill\MassBill;
use unclead\multipleinput\MultipleInput;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model MassBill */

MassBillAsset::register($this);

$this->title = 'Подтвердите выставление счетов';

$form = ActiveForm::begin(['id' => 'createMassBill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/mass-bill/confirm']]);

echo $form->field($model, 'type', [
    'options' => ['style' => 'display:none;'],
    'template' => '{input}'])
    ->textInput();

echo $form->field($model, 'period', [
    'options' => ['style' => 'display:none;'],
    'template' => '{input}'])
    ->textInput();

echo $form->field($model, 'comment', [
    'options' => ['style' => 'display:none;'],
    'template' => '{input}'])
    ->textInput();

echo $form->field($model, 'sum', [
    'options' => ['style' => 'display:none;'],
    'template' => '{input}'])
    ->textInput();

echo $form->field($model, 'payUpDate', [
    'options' => ['style' => 'display:none;'],
    'template' => '{input}'])
    ->input('date');

try {
    echo $form->field($model, 'bills')->widget(MultipleInput::class, [
        'id' => 'w_bills',
        'allowEmptyList' => true,
        'min' => 0,
        'addButtonOptions' => [
            'class' => 'hidden'
        ],
        'enableError' => true,
        'attributeOptions' => [
            'enableAjaxValidation' => true,
            'enableClientValidation' => false,
            'validateOnChange' => true,
            'validateOnSubmit' => true,
            'validateOnBlur' => false,
        ],
        'columns' => [
            [
                'name' => 'cottage',
                'type' => 'textInput',
                'title' => 'Участок',
                'options' => ['readonly' => true],
            ],
            [
                'name' => 'type',
                'type' => 'textInput',
                'title' => 'Тип счёта',
                'options' => ['readonly' => true],
            ],
            [
                'name' => 'period',
                'type' => 'textInput',
                'title' => 'Период',
                'options' => ['readonly' => true],
            ],
            [
                'name' => 'sum',
                'type' => 'textInput',
                'title' => 'Сумма',
                'options' => ['type' => 'number', 'min' => 0, 'step' => '0.01']
            ],
            [
                'name' => 'hasMail',
                'type' => 'checkbox',
                'title' => 'Отправить email',
            ],
            [
                'name' => 'printInvoice',
                'type' => 'checkbox',
                'title' => 'Распечатать квитанцию',
            ],
        ]

    ]);
} catch (Exception $e) {
    echo $e->getTraceAsString();
}

$totalAmount = 0;

foreach ($model->bills as $bill) {
    $totalAmount += CashHandler::toRubles($bill['sum']);
}

$totalAmount = CashHandler::toRubles($totalAmount);

echo "<table class='table table-condensed'><caption>Итого:</caption>
<thead><tr><th>Всего счетов</th><th>Общая сумма</th><th>Комментарий</th><th>Срок оплаты</th></tr></thead>
<tbody><tr><td>" . count($model->bills) . "</td><td>$totalAmount</td><td>$model->comment</td><td>$model->payUpDate</td></tr></tbody>
</table>";

echo Html::submitButton('Создать счета', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);

ActiveForm::end();
