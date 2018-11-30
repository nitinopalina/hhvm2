<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\modules\user\models\ResetPasswordForm */

$this->title = Yii::t('frontend', 'Reset password');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="resetPasswordPopup">
    <h1>Reset Password</h1>
    <?php if (!empty($error)) { ?>
        <?= $error ?>
    <?php } ?>
    <form id="reset_password" method="post">
        <div class="alert alert-danger" style="display: none"></div>
        <div id="message" style="display: none"></div>
        <div class="error-summary" style="display:none"><ul></ul></div> 
        <div class="LoginUser">
            <label for="new_password_hash">New Password *</label>
            <input type="password" id="new_password_hash" name="User[new_password_hash]" value="">       
        </div>
        <div class="loginPassword">
            <label for="confirm_password_hash">Confirm Password *</label>
            <input type="password" id="confirm_password_hash" name="User[confirm_password_hash]" value="">      
        </div>
        <div class="login_btnn">
            <label></label>
            <button id="login_button" class="btn btn-warning btn-lg customLoginBtn"> Submit </button> 
        </div>
    </form>

</div>
<script>

    $(document).ready(function () {

        $("#new_password_hash").focusout(function () {
            var new_password_hash = $("#new_password_hash").val();
            if (new_password_hash == '') {
                $("#new_password_hash").focus();
                $(".alert-danger").css({"display": "block"});
                $(".alert-danger").html("New Password cannot be blank.!");
                $("#new_password_hash").css({"border": "1px solid #a94442"});
                $("#new_password_hash").css({"background": "#f2dede"});
                $("label[for='new_password_hash']").css({"color": "#a94442"});
            }
        });
        $("#new_password_hash").keypress(function () {
            $(".alert-danger").css({"display": "none"});
            $("#new_password_hash").css({"border": "1px solid #A9A9A9"});
            $("#new_password_hash").css({"background": "#ffffff"});
            $("label[for='new_password_hash']").css({"color": "#333 !important"});

        });
        $("#confirm_password_hash").focusout(function () {
            var password = $("#confirm_password_hash").val();
            if (password == '') {
                $(".alert-danger").css({"display": "block"});
                $(".alert-danger").html("Confirm Password cannot be blank.!");
                $("#confirm_password_hash").css({"border": "1px solid #a94442"});
                $("#confirm_password_hash").css({"background": "#f2dede"});
                $("label[for='confirm_password_hash']").css({"color": "#a94442"});
            }
        });
        $("#confirm_password_hash").keypress(function () {
            $(".alert-danger").css({"display": "none"});
            $("#confirm_password_hash").css({"border": "1px solid #A9A9A9"});
            $("#confirm_password_hash").css({"background": "#ffffff"});
            $("label[for='confirm_password_hash']").css({"color": "#333 !important"});
        });

        $('#reset_password').on("submit", function (e) {
            var new_password_hash = $("#new_password_hash").val();
            var password = $("#confirm_password_hash").val();
            if (password == '' && new_password_hash == '') {
                $(".alert-danger").css({"display": "block"});
                $(".alert-danger").html("New Password and Confirm Password cannot be blank.!");
                $("#new_password_hash").css({"border": "1px solid #a94442"});
                $("#new_password_hash").css({"background": "#f2dede"});
                $("label[for='new_password_hash']").css({"color": "#a94442"});
                $("#confirm_password_hash").css({"border": "1px solid #a94442"});
                $("#confirm_password_hash").css({"background": "#f2dede"});
                $("label[for='confirm_password_hash']").css({"color": "#a94442"});
                return false;
            }
            if (password != '' && new_password_hash != '') {
                if (password != new_password_hash) {
                    $(".alert-danger").css({"display": "block"});
                    $(".alert-danger").html("Confirm Password is not same as New Password.!");
                    $("#new_password_hash").css({"border": "1px solid #a94442"});
                    $("#new_password_hash").css({"background": "#f2dede"});
                    $("label[for='new_password_hash']").css({"color": "#a94442"});
                    $("#confirm_password_hash").css({"border": "1px solid #a94442"});
                    $("#confirm_password_hash").css({"background": "#f2dede"});
                    $("label[for='confirm_password_hash']").css({"color": "#a94442"});
                    return false;
                }
                else {

                    $.ajax({
                        url: JS_BASE_URL + 'user/verify',
                        type: 'POST',
                        data: $("#reset-password").serialize(),
                        beforeSend: function (result) {
                            $(".loading").css({"display": "inline-block"});
                        },
                        success: function (result) {
                            if (result == '') {
                                $("#message").css({"display": "block"});
                                $("#message").html(result);
                            }
                            $(".loading").css({"display": "none"});
                        }
                    });
                }
            }
        });


    });
</script>