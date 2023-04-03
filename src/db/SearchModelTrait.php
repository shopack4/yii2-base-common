<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\db;

use shopack\base\common\rest\enuColumnInfo;

trait SearchModelTrait
{
  public function applySearchValuesInQuery($query)
  {
    $columnsInfo = static::columnsInfo();
    foreach ($columnsInfo as $column => $info) {
      if (empty($info[enuColumnInfo::search]))
        continue;

      if (is_bool($info[enuColumnInfo::search])) {
        $query->andFilterWhere([$column => $this->$column]);
      } else {
        $query->andFilterWhere([$info[enuColumnInfo::search], $column, $this->$column]);
      }
    }
  }

}
