<?php

use app\assets\AuthAsset;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Необходима аутентификация';

AuthAsset::register($this);

?>
<div class="site-login">
    <div class="text-center">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>Доступ ограничен!</p>

        <p>Заполните поля для входа:</p>
    </div>

    <div class="row">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

            <?= $form->field($auth, 'name')->textInput(['autofocus' => true])->hint('Введите логин.')->label('Имя пользователя') ?>

            <?= $form->field($auth, 'password')->passwordInput()->hint('Введите пароль.')->label('Пароль') ?>

            <div class="form-group">
                <?= Html::submitButton('Вход', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>