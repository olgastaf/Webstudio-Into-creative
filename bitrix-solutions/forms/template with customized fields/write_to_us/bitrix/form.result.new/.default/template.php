<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<?if ($arResult["isFormErrors"] == "Y"):?>
    <?=$arResult["FORM_ERRORS_TEXT"];?>
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
            ?>
            <?if (isset($arResult['FORM_ERRORS'][$FIELD_SID])):?>
                <span class="error-fld" title="<?=htmlspecialcharsbx($arResult["FORM_ERRORS"][$FIELD_SID])?>"></span>
            <?endif;?>

            <?
            $html = $arQuestion["HTML_CODE"];
            $fieldType = $arQuestion["STRUCTURE"]["0"]["FIELD_TYPE"] ?? '';
			
			switch ($fieldType) {
				case 'textarea':
				    // вывод textarea
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
					break;
				
				default:
				    // вывод обычных input: text, email и т. п.
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
					break;
			}
		}
        ?>
		<?$APPLICATION->IncludeComponent(
		    "bitrix:main.userconsent.request",
		    "",
		    [
		        "ID" => 1, // Стандартное соглашение битрикса. Вместо него можно поставить собственное созданное соглашение с нужным id
		        "AUTO_SAVE" => "Y",
		        "IS_LOADED" => "N",
		        "IS_CHECKED" => "N",
		        "REPLACE" => [
		            "button_caption" => $arResult["arForm"]["BUTTON"],
		            "fields" => [
		                "Имя",
		                "Телефон",
		                "Email"
		            ],
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

    <?=$arResult["FORM_FOOTER"]?>
	</div>
<?endif;?>
