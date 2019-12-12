<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use app\models\Player;

/**
 * LoginForm is the model behind the login form.
 *
 * @property Player|null $player This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_player = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $player = $this->getPlayer();
            if (!$player || !$player->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * Logs in a player using the provided username and password.
     * @return bool whether the player is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getPlayer(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds player by [[username]]
     *
     * @return Player|null
     */
    public function getPlayer()
    {
        if ($this->_player === false) {
            $this->_player = Player::findByUsername($this->username);
        }

        return $this->_player;
    }
}