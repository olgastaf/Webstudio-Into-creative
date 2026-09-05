<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<?if ($arResult["isFormErrors"] == "Y"):?>
    <?=$arResult["FORM_ERRORS_TEXT"];?>
<?endif;?>

<?=$arResult["FORM_NOTE"]?>

<?if ($arResult["isFormNote"] != "Y"):?>

    <?=$arResult["FORM_HEADER"] // Здесь появляется сообщение об ошибках ?>

    <form class="page__form" name="<?=$arResult["WEB_FORM_NAME"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
        <?=bitrix_sessid_post();?>
        <input type="hidden" name="WEB_FORM_ID" value="<?=$arParams["WEB_FORM_ID"]?>">
        <input type="hidden" name="web_form_submit" value="Y">

        <?
        foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion)
        {
            ?>
            <?if (isset($arResult['FORM_ERRORS'][$FIELD_SID])):?>
                <span class="error-fld" title="<?=htmlspecialcharsbx($arResult["FORM_ERRORS"][$FIELD_SID])?>"></span>
            <?endif;?>

            <?
            $html = $arQuestion["HTML_CODE"];

            if (stripos($html, '<textarea') !== false)
            {
                $html = preg_replace(
                    '/<textarea\b/i',
                    '<textarea id="prop_' . $FIELD_SID . '"',
                    $html,
                    1
                );
                ?>
                <div class="reviews__input textarea page__input prop_<?=$FIELD_SID?>">
                    <?=$html?>
                    <label for="prop_<?=$FIELD_SID?>">
                        <?=$arQuestion["CAPTION"]?>
                        <?if ($arQuestion["REQUIRED"] == "Y"):?>
                            <?=$arResult["REQUIRED_SIGN"];?>
                        <?endif;?>
                    </label>
                </div>
                <?
            }
            elseif (stripos($html, 'type="checkbox"') !== false)
            {
                $answer = $arQuestion["STRUCTURE"][0];
                $value = CForm::GetCheckBoxValue($FIELD_SID, $answer, $arResult["arrVALUES"]);
                ?>
                <div class="modal__form-policy prop_<?=$FIELD_SID?>">
                    <?=CForm::GetCheckBoxField(
                        $FIELD_SID,
                        $answer["ID"],
                        $value,
                        'class="styled-checkbox" id="prop_'.$FIELD_SID.'"'
                    );?>
                    <label for="prop_<?=$FIELD_SID?>">
						<? //echo '<pre>'.print_r($arQuestion,true).'</pre>';?>
						<?//=$arQuestion["CAPTION"]?>
						<span>Даю согласие на обработку персональных данных в целях обработки заявки и обратной связи на условиях <a href="/privacy-policy/">Политики конфиденциальности</a></span>
                        <?if ($arQuestion["REQUIRED"] == "Y"):?>
                            <?=$arResult["REQUIRED_SIGN"];?>
                        <?endif;?>
                    </label>
                </div>
                <?
            }
            else
            {
                $html = preg_replace(
                    '/<input\b/i',
                    '<input id="prop_' . $FIELD_SID . '"',
                    $html,
                    1
                );
                ?>
                <div class="reviews__input page__input prop_<?=$FIELD_SID?>">
                    <?=$html?>
                    <label for="prop_<?=$FIELD_SID?>">
                        <?=$arQuestion["CAPTION"]?>
                        <?if ($arQuestion["REQUIRED"] == "Y"):?>
                            <?=$arResult["REQUIRED_SIGN"];?>
                        <?endif;?>
                    </label>
                </div>
                <?
            }
        }
        ?>

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
		<div class="require">
            <?=$arResult["REQUIRED_SIGN"];?> - <?=GetMessage("FORM_REQUIRED_FIELDS")?>
        </div>
        <div>
            <input
                <?=(intval($arResult["F_RIGHT"]) < 10 ? 'disabled="disabled"' : '');?>
                class="btn reviews__btn modal__form-send"
                type="submit"
                name="web_form_submit"
                value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>"
            />
        </div>


    </form>

    <?//=$arResult["FORM_FOOTER"]?>

<?endif;?>