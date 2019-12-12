<?php

namespace app\modules\target\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\data\ArrayDataProvider;
use app\modules\target\models\Target;
use app\modules\target\models\Finding;
use app\modules\target\models\Treasure;
use app\models\PlayerFinding;
use app\models\PlayerTreasure;
use yii\filters\AccessControl;
use yii\helpers\Html;
/**
 * Default controller for the `target` module
 */
class DefaultController extends Controller
{
      public function behaviors()
      {
          return [
              'access' => [
                  'class' => AccessControl::className(),
                  'only' => ['index', 'claim'],
                  'rules' => [
                      [
                          'allow' => true,
                          'actions' => ['index'],
                      ],
                      [
                          'allow' => true,
                          'actions' => ['claim'],
                          'roles' => ['@'],
                          'verbs'=>['post'],
                      ],
                  ],
              ],
          ];
      }
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex($id)
    {
      $sum=0;
      $userTarget=null;
      if(Yii::$app->user->isGuest)
        $target=$this->findModel($id);
      else
      {
        $target=Target::find()->where(['t.id'=>$id])->player_progress(Yii::$app->user->id)->one();
        $PF=PlayerFinding::find()->joinWith(['finding'])->where(['player_id'=>Yii::$app->user->id,'finding.target_id'=>$id])->all();
        $PT=PlayerTreasure::find()->joinWith(['treasure'])->where(['player_id'=>Yii::$app->user->id,'treasure.target_id'=>$id])->all();
        foreach($PF as $pf)
          $sum+=$pf->finding->points;
        foreach($PT as $pt)
          $sum+=$pt->treasure->points;
      }
      $treasures=$findings=[];
      foreach($target->treasures as $treasure)
        $treasures[]=$treasure->id;
      foreach($target->findings as $finding)
        $findings[]=$finding->id;
      $model=\app\models\Stream::find()->select('stream.*,TS_AGO(ts) as ts_ago')
      ->where(['model_id'=>$findings, 'model'=>'finding'])
      ->orWhere(['model_id'=>$treasures, 'model'=>'treasure'])->orderBy(['ts'=>SORT_DESC]);
      $dataProvider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSizeParam'=>'stream-perpage',
                'pageParam'=>'stream-page',
                'pageSize' => 10,
            ]
      ]);



      $headshotsProvider = new ArrayDataProvider([
            'allModels' => $target->headshots,
            'pagination' => [
                'pageSizeParam'=>'headshot-perpage',
                'pageParam'=>'headshot-page',
                'pageSize' => 10,
            ]]);

      return $this->render('index', [
            'target' => $target,
            'streamProvider'=>$dataProvider,
            'playerPoints'=>$sum,
            'headshotsProvider'=>$headshotsProvider
        ]);
    }
    public function actionClaim()
    {
        $string = Yii::$app->request->post('hash');
        //$string = Yii::$app->request->get('hash');
        if(empty($string)) return $this->renderAjax('claim');
        $treasure=Treasure::find()->claimable()->byCode($string)->one();
        if($treasure!==null && Treasure::find()->byCode($string)->claimable()->notBy(Yii::$app->user->id)->one()===null)
        {
          Yii::$app->session->setFlash('warning',sprintf('Flag [%s] claimed before',$treasure->name,$treasure->target->name));
          return $this->renderAjax('claim');
        }
        elseif($treasure===null)
        {
          Yii::$app->session->setFlash('error',sprintf('Flag [<strong>%s</strong>] do not exist!',Html::encode($string)));
          return $this->renderAjax('claim');
        }

        $connection=Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try {
          if($treasure!==null)
          {
            $PT=new PlayerTreasure();
            $PT->player_id=Yii::$app->user->id;
            $PT->treasure_id=$treasure->id;
            $PT->save();
            if($treasure->appears!==-1)
            {
              $treasure->updateAttributes(['appears' => intval($treasure->appears)-1]);
            }
          }
          $transaction->commit();
          Yii::$app->session->setFlash('success',sprintf('Flag [%s] claimed for %s points',$treasure->name,number_format($treasure->points)));
        }
        catch (\Exception $e)
        {
          $transaction->rollBack();
          Yii::$app->session->setFlash('error','Flag failed');
          throw $e;
        }
        catch (\Throwable $e)
        {
          $transaction->rollBack();
          throw $e;
        }
        return $this->renderAjax('claim');
    }

    /**
     * Finds the Target model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Target the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = \app\modules\target\models\Target::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}