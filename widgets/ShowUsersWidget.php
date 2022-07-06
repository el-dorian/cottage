<?php

namespace app\widgets;

use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_target;
use app\models\selections\TargetInfo;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_target;
use app\models\TargetHandler;
use app\models\User;
use yii\base\Widget;
use yii\helpers\Html;

class ShowUsersWidget extends Widget
{

    /**
     * @var User[]
     */
    public array $users;
    public string $content = '';

    public function init():void
    {
        if(!empty($this->users)){
            $this->content = '<table class="table table-condensed">';
            foreach ($this->users as $user) {
                $this->content .= "<tr><td>$user->username</td><td><div class='btn-group'><button class='btn btn-info ajax-post-trigger' data-action='/personal-area/change-pass?id=$user->id'><span class='glyphicon glyphicon-refresh'></span></button><button class='btn btn-danger ajax-post-trigger' data-action='/personal-area/delete?id=$user->id'><span class='glyphicon glyphicon-trash'></span></button></div></td></tr>";
            }
            $this->content .= '</table>';
        }
    }
    /**
     * @return string
     */
    public function run():string
    {
        return Html::decode($this->content);
    }
}