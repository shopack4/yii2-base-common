<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\components;

class I18N extends \yii\i18n\I18N
{
  public function translate($category, $message, $params, $language)
  {
    try {
      $messageSource = $this->getMessageSource($category);
      $translation = $messageSource->translate($category, $message, $language);
      if ($translation === false) {
        return $this->format($message, $params, $messageSource->sourceLanguage);
      }
      return $this->format($translation, $params, $language);
    } catch (\Exception $e) {
    } catch (\Throwable $e) {
    }

    return $this->format($message, $params, $language);
  }

}
