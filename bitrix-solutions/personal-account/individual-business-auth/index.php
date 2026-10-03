<style>
.register-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.register-modal[hidden] {
  display: none;
}

.register-modal__overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, .55);
}

.register-modal__box {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
  max-height: 90vh;
  overflow: auto;
  background: #fff;
  border-radius: 12px;
  padding: 28px;
}

.register-modal__close {
  position: absolute;
  top: 10px;
  right: 14px;
  border: 0;
  background: none;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
}
</style>

<button type="button" class="btn btn-primary" id="open-register">Регистрация</button>

<div id="register-modal" class="register-modal" hidden>
  <div class="register-modal__overlay"></div>
  <div class="register-modal__box">
    <button type="button" class="register-modal__close" aria-label="Закрыть">&times;</button>
<?php
if (!defined('INDIVIDUAL_BUSINESS_REGISTRATION')) {
    define('INDIVIDUAL_BUSINESS_REGISTRATION', true);
}
?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:main.register",
        "individual-business-reg",
        Array(
            "SHOW_FIELDS" => ["EMAIL", "NAME", "LAST_NAME", "PERSONAL_PHONE", "WORK_COMPANY"],
            "USER_PROPERTY" => ["UF_CLIENT_TYPE", "UF_INN", "UF_KPP", "UF_BUSINESS_ADDRESS"],
            "REQUIRED_FIELDS" => ["EMAIL", "NAME", "PERSONAL_PHONE"],
            "AUTH" => "Y",
            "USE_BACKURL" => "Y",
            "SUCCESS_PAGE" => "/personal/",
            "SET_TITLE" => "N"
        ),
        false
    );?>
  </div>
</div>
<script>
$(function () {
  const $modal = $('#register-modal');
if ($modal.find('[data-registration-open="Y"]').length) {
    $modal.removeAttr('hidden');
    $('body').css('overflow', 'hidden');
}
  $('#open-register').on('click', function () {
    $modal.removeAttr('hidden');
    $('body').css('overflow', 'hidden');
  });

  $('.register-modal__close, .register-modal__overlay').on('click', function () {
    $modal.attr('hidden', 'hidden');
    $('body').css('overflow', '');
  });

  $(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
      $modal.attr('hidden', 'hidden');
      $('body').css('overflow', '');
    }
  });
});
</script>