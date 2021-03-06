<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\gameplay\models\TargetVolume */

$this->title='Update Target Volume: '.$model->target_id;
$this->params['breadcrumbs'][]=ucfirst(Yii::$app->controller->module->id);
$this->params['breadcrumbs'][]=['label' => 'Target Volumes', 'url' => ['index']];
$this->params['breadcrumbs'][]=['label' => $model->target_id, 'url' => ['view', 'target_id' => $model->target_id, 'volume' => $model->volume]];
$this->params['breadcrumbs'][]='Update';
?>
<div class="target-volume-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
