<?php

namespace app\modules\help\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\help\models\Instruction;

/**
 * InstructionSearch represents the model behind the search form of `app\modules\help\models\Instruction`.
 */
class InstructionSearch extends Instruction
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'weight'], 'integer'],
            [['title', 'player_type', 'message', 'ts'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Instruction::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'weight' => $this->weight,
            'ts' => $this->ts,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'player_type', $this->player_type])
            ->andFilterWhere(['like', 'message', $this->message]);

        return $dataProvider;
    }
}