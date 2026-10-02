<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Настройки шаблона
$consentAgreementId = 1;

// Поля, которые будут перечислены в тексте соглашения
$consentFields = [
    "Имя",
    "Телефон",
    "Email",
];
?>

<?if ($arResult["isFormErrors"] == "Y"):?>
    <div class="ic-form__errors">
        <?=$arResult["FORM_ERRORS_TEXT"];?>
    </div>
<?endif;?>

<?=$arResult["FORM_NOTE"]?>

<?if ($arResult["isFormNote"] != "Y"):?>

    <?=$arResult["FORM_HEADER"] // Здесь появляется сообщение об ошибках ?>
    <div class="ic-form">
        <?=bitrix_sessid_post();?>
        <input type="hidden" name="WEB_FORM_ID" value="<?=$arParams["WEB_FORM_ID"]?>">
        <input type="hidden" name="web_form_submit" value="Y">

        <?
      foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion)
      {
          $html = $arQuestion["HTML_CODE"];
          $fieldType = $arQuestion["STRUCTURE"][0]["FIELD_TYPE"] ?? '';

          // ID поля для связи input/textarea с label.
          // Если Bitrix уже сформировал id — сохраняем его.
          // Если id нет — создаём собственный.
          $fieldId = 'prop_' . $FIELD_SID;

          $hasId = preg_match(
              '/\bid=["\']([^"\']+)["\']/i',
              $html,
              $matches
          );

          if ($hasId) {
              $fieldId = $matches[1];
          }

          switch ($fieldType) {

              case 'textarea':

                  // Добавляем id только в том случае,
                  // если Bitrix не добавил его самостоятельно.
                  if (!$hasId) {
                      $html = preg_replace(
                          '/<textarea\b/i',
                          '<textarea id="' . htmlspecialcharsbx($fieldId) . '"',
                          $html,
                          1
                      );
                  }
                  ?>

                  <div class="ic-form__field ic-form__field--textarea prop_<?<?=htmlspecialcharsbx($FIELD_SID)?>
                      <?=$html?>

                      <label for="<?=htmlspecialcharsbx($fieldId)?>">
                          <?=$arQuestion["CAPTION"]?>

                          <?if ($arQuestion["REQUIRED"] == "Y"):?>
                              <?=$arResult["REQUIRED_SIGN"];?>
                          <?endif;?>
                      </label>
                  </div>

                  <?
                  break;


              default:

                  // Обычные текстовые input: text, email и т. п.
                  // Добавляем id только в том случае,
                  // если Bitrix не добавил его самостоятельно.
                  if (!$hasId) {
                      $html = preg_replace(
                          '/<input\b/i',
                          '<input id="' . htmlspecialcharsbx($fieldId) . '"',
                          $html,
                          1
                      );
                  }
                  ?>

                  <div class="ic-form__field prop_<?<?=htmlspecialcharsbx($FIELD_SID)?>
                      <?=$html?>

                      <label for="<?=htmlspecialcharsbx($fieldId)?>">
                          <?=$arQuestion["CAPTION"]?>

                          <?if ($arQuestion["REQUIRED"] == "Y"):?>
                              <?=$arResult["REQUIRED_SIGN"];?>
                          <?endif;?>
                      </label>
                  </div>

                  <?
                  break;
          }
      }
        ?>
		<?$APPLICATION->IncludeComponent(
		    "bitrix:main.userconsent.request",
		    "ic-consent",
		    [
		        "ID" => $consentAgreementId, // Стандартное соглашение битрикса. Вместо него можно поставить собственное созданное соглашение с нужным id
		        "AUTO_SAVE" => "Y",
		        "IS_LOADED" => "N",
		        "IS_CHECKED" => "N",
		        "REPLACE" => [
		            "button_caption" => $arResult["arForm"]["BUTTON"],
		            "fields" => $consentFields,
		        ],
		    ]
		);?>
        <?if ($arResult["isUseCaptcha"] == "Y"):?>
            <div>
                <b><?=GetMessage("FORM_CAPTCHA_TABLE_TITLE")?></b>
            </div>
            <div>
                <div class="td">
                    <input type="hidden" name="captcha_sid" value="<?=htmlspecialcharsbx($arResult["CAPTCHACode"]);?>" />
                    <img src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($arResult["CAPTCHACode"]);?>" width="180" height="40" />
                </div>
            </div>
            <div>
                <div class="td"><?=GetMessage("FORM_CAPTCHA_FIELD_TITLE")?><?=$arResult["REQUIRED_SIGN"];?></div>
                <div class="td">
                    <input type="text" name="captcha_word" size="30" maxlength="50" value="" class="inputtext" />
                </div>
            </div>
        <?endif;?>
		<div class="ic-form__required-note">
            <?=$arResult["REQUIRED_SIGN"];?> - <?=GetMessage("FORM_REQUIRED_FIELDS")?>
        </div>
        <div>
            <input
                <?=(intval($arResult["F_RIGHT"]) < 10 ? 'disabled="disabled"' : '');?>
                class="ic-form__submit"
                type="submit"
                name="web_form_submit"
                value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>"
            />
        </div>
	</div>
    <?=$arResult["FORM_FOOTER"]?>
<?endif;?>
