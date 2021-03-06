<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\settings\models\Objective */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="objective-form">

    <?php $form=ActiveForm::begin();?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true])->hint('A unique title for the objective') ?>

    <?= $form->field($model, 'player_type')->dropDownList(['offense' => 'Offense', 'defense' => 'Defense', 'both' => 'Both', ], ['prompt' => 'Choose player type']) ?>

    <?= $form->field($model, 'message')->textarea(['rows' => 6])->hint('Detailed body for the objective in raw HTML') ?>

    <?= $form->field($model, 'weight')->textInput()->hint('Define ordering of the displayed objectives by weight in asceding order') ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end();?>

</div>
