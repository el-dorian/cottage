<?php

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\web\View;



/* @var $this View */

AppAsset::register($this);

?>

<div class="site-error">
    <h1>Ошибка доступа</h1>

    <div class="alert alert-danger">
        Вы не можете выполнить данный запрос.<br/>
        Вы можете выполнить выход из учётной записи и войти в систему заново в учётную запись с повышенными правами доступа.
    </div>
    <p>
        <?php
        echo Html::beginForm(['/logout'], 'post', ['class' => 'form-inline'])
        . Html::submitButton(
            'Выход (' . Yii::$app->user->identity->username . ')',
            ['class' => 'btn btn-info logout']
        )
        . Html::endForm()
        ?>
    </p>
</div>
