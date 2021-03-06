<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\activity\models\SessionsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title=Yii::t('app', 'Sessions');
$this->params['breadcrumbs'][]=$this->title;
?>
<div class="sessions-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Delete expired'), ['delete-expired'], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete all expired sessions?'),
                'method' => 'post',
            ],
        ]) ?>

    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'player_id',
            [
                'attribute' => 'player',
                'label'=>'Player',
                'value'=> function($model) {return sprintf("id:%d %s", $model->player_id, $model->player ? $model->player->username : "");},
            ],
            'ipoctet',
            'id',
            'expire:dateTime',
            'ts:dateTime',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]);?>


</div>
