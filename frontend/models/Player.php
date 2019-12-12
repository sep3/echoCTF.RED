<?php
namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Player model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class Player extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    public $confirm_password;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'player';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            /* fullname rules */
            [['fullname'], 'trim'],
            [['fullname'], 'string', 'max'=>32],

            /* email field rules */
            [['email'], 'trim'],
            [['email'], 'string','max'=>255],
            [['email'], 'email'],

            /* username field rules */
            [['username'], 'trim'],
            [['username'], 'string', 'max'=>32],
            [['username'], '\app\components\validators\LowerRangeValidator', 'not'=>true, 'range'=>['admin','administrator','echoctf','root','support']],
            [['username'], 'required', 'message' => 'Please choose a username.'],

            /* active field rules */
            [['active'], 'filter', 'filter' => 'boolval'],
            [['active'], 'default', 'value' => false],

            /* status field rules */
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'default', 'value' => self::STATUS_INACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            /* password field rules */

            [['password','confirm_password'], 'string', 'max'=>255,'on'=>['password_change']],
            [['password'], 'compare', 'compareAttribute'=>'confirm_password','on'=>['password_change']],

/*            array('activkey','required','on'=>'reset'),
      			array('active','required','on'=>'quick_activation'),
      			array('username, fullname, email', 'required'),
      			array('email','email','skipOnError'=>false,'on'=>'update'),
      			array('username, fullname, email', 'filter','filter'=>'trim'),
      			array('username',
          				'match', 'not' => true, 'pattern' => '/[^a-zA-Z0-9]/',
          				'message' => 'Invalid characters in username.',
          	),
      			array('username', 'unique',
          				'allowEmpty' => false,
          				'className' => 'Player',
          				'attributeName'=>'username',
          				'caseSensitive' => false,
          				'message' => 'Another player already exists with this name.',
        		),
      			array('email', 'unique',
          				'allowEmpty' => true,
          				'className' => 'Player',
          				'attributeName'=>'email',
          				'caseSensitive' => false,
          				'message' => 'Another user has already registered with this email address.',
                  'skipOnError'=>true
          		),
      			array('username, fullname, email, playerrd,confirm_playerrd', 'length', 'max'=>255),
          	array('created','default','value'=>new CDbExpression('NOW()')),
      			array('academic','boolean'),
          	array('ts','default','value'=>new CDbExpression('NOW()')),
      			array('type', 'length', 'max'=>7), */
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds player by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds player by playerrd reset token
     *
     * @param string $token playerrd reset token
     * @return static|null
     */
    public static function findByplayerrdResetToken($token)
    {
        if (!static::isplayerrdResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'playerrd_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds player by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if playerrd reset token is valid
     *
     * @param string $token playerrd reset token
     * @return bool
     */
    public static function isplayerrdResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.playerrdResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current player
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function getIsAdmin()
    {
      return $this->admin;
    }
   /**
    * Get status Label
    */
    public function getStatusLabel()
    {
      switch($this->status) {
        case self::STATUS_ACTIVE:
          return 'ACTIVE';
        case self::STATUS_INACTIVE:
          return 'INACTIVE';
        case self::STATUS_DELETED:
          return 'DELETED';
      }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['player_id' => 'id']);
    }
    public function getPlayerScore()
    {
        return $this->hasOne(PlayerScore::className(), ['player_id' => 'id']);
    }
    public function getSSL()
    {
      return $this->hasOne(PlayerSsl::className(), ['player_id' => 'id']);
    }
    public function getSpins()
    {
      $command = Yii::$app->db->createCommand('select counter from player_spin WHERE player_id=:player_id');
      return (int)$command->bindValue(':player_id',$this->id)->queryScalar();
    }
    public function getPlayerTreasures()
    {
        return $this->hasMany(PlayerTreasure::className(), ['player_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTreasures()
    {
        return $this->hasMany(Treasure::className(), ['id' => 'treasure_id'])->viaTable('player_treasure', ['player_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlayerFindings()
    {
        return $this->hasMany(PlayerFinding::className(), ['player_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFindings()
    {
        return $this->hasMany(Finding::className(), ['id' => 'finding_id'])->viaTable('player_finding', ['player_id' => 'id']);
    }

    public function isAdmin()
    {
      $admin_ids=[1];
      return array_search(intval($this->id),$admin_ids)!==FALSE; // error is here
    }

    public function getProgress()
    {
  		$targets=Target::model()->player_progress($this->id);
  		$targets->getDbCriteria()->mergeWith(array('having'=>'player_findings>0 or player_treasures>0'));
  		return $targets;
    }

/* XXXREMOVEXXX
   public static function createQuery()
    {
      return new TargetQuery(['modelClass' => get_called_class()]);
    }
*/
    public function getNotifications()
    {
        return $this->hasMany(Notification::className(), ['player_id' => 'id']);
    }
    public function getPendingNotifications()
    {
        return $this->hasMany(Notification::className(), ['player_id' => 'id'])->pending();
    }

    public function getPlayerHints()
    {
        return $this->hasMany(PlayerHint::className(), ['player_id' => 'id']);
    }
    public function getPendingHints()
    {
        return $this->hasMany(PlayerHint::className(), ['player_id' => 'id'])->pending();
    }

    public static function find()
    {
        return new PlayerQuery(get_called_class());
    }

}