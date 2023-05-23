<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\db;

use Yii;
use Closure;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;
use yii\base\NotSupportedException;

trait SoftDeleteActiveRecordTrait
{
  public $softdelete_RemovedStatus = 'R';
  // public $softdelete_StatusField;
  public $softdelete_RemovedAtField;
  public $softdelete_RemovedByField;
  public $softdelete_CustomLambda;

  public function initSoftDelete() { }

  public function softDelete()
  {
    $statusColumnName = $this->getStatusColumnName();

    $this->initSoftDelete();

    if (empty($this->softdelete_RemovedStatus)
        || empty($this->statusColumnName)
        || empty($this->softdelete_RemovedAtField)
        || empty($this->softdelete_RemovedByField))
      throw new UnprocessableEntityHttpException('soft delete not initialized');

    $this->setAttribute($this->statusColumnName, $this->softdelete_RemovedStatus);
    $this->setAttribute($this->softdelete_RemovedAtField, new Expression('UNIX_TIMESTAMP()'));

    if (isset(Yii::$app->user->identity) && (Yii::$app->user->getIsGuest() == false))
      $this->setAttribute($this->softdelete_RemovedByField, Yii::$app->user->id);

    if ($this->softdelete_CustomLambda instanceof Closure
        || (is_array($this->softdelete_CustomLambda)
          && is_callable($this->softdelete_CustomLambda)))
      call_user_func($this->softdelete_CustomLambda);

    return $this->save();
  }

  public function softUndelete()
  {
    $this->initSoftDelete();

    throw new NotSupportedException(__METHOD__ . ' is not supported yet.');
  }

  protected function deleteInternal()
  {
    try
    {
      return parent::deleteInternal(); //HARD DELETE
    } catch(\Throwable $th) {
      if ($this->softDelete())
        return 1;
      return false;
    }
  }

  public function undelete()
  {
    $this->softUndelete();
  }

}
