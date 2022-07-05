<?php

use app\assets\CallingAsset;
use app\models\database\Calling;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $state Calling[] */

CallingAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = "Обзвон";

// посчитаю статусы и выдам список контактов

$states = [
    'not_called' => 'Не обзвонен',
    'not_available' => 'Не берут трубку',
    'will_come' => 'Придёт',
    'will_not_come' => 'Не придёт',
];

$notCalledCounter = 0;
$notAvailableCounter = 0;
$willComeCounter = 0;
$willNotComeCounter = 0;
$totalCalledCounter = 0;

if(!empty($state)){
    $counter = 1;
    echo "<table class='table table-condensed table-striped'>";
    $stateColor = '';
    foreach ($state as $item) {
        switch ($item->state){
            case 'not_called' :
                $notCalledCounter++;
                $stateColor = 'text-info';
                break;
            case 'not_available' :
                $notAvailableCounter++;
                $totalCalledCounter++;
                $stateColor = 'text-warning';
                break;
            case 'will_come' :
                $willComeCounter++;
                $totalCalledCounter++;
                $stateColor = 'text-success';
                break;
            case 'will_not_come' :
                $willNotComeCounter++;
                $totalCalledCounter++;
                $stateColor = 'text-danger';
                break;
        }
        echo "<tr class='$stateColor'><td>$counter</td><td>Дача № $item->cottage</td><td>$item->phonesData</td><td>" . Html::dropDownList("state_$item->cottage", $item->state, $states, ['data-cottage' => $item->cottage]) . "</td></tr>";
        ++$counter;
    }
    echo '</table>';

}
?>

<footer class="footer mt-auto py-3 text-muted navbar-fixed-bottom">
    <div class="container">
        <div class="btn-group"><button class="btn btn-info">Обзвонено: <span id="totalCalledSpan"><?=$totalCalledCounter?></span></button><button class="btn btn-success">Придут: <span id="willComeSpan"><?=$willComeCounter?></span></button><button class="btn btn-danger">Не придут: <span id="willNotComeSpan"><?=$willNotComeCounter?></span></button><button class="btn btn-warning">Недозвон: <span id="notAvailableSpan"><?=$notAvailableCounter?></span></button></div>
    </div>
</footer>
