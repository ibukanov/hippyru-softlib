<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

/**
 * Display login/logout form,
 * using $strUserName variable.
 */

function display_form_login () {
    global $strUserName;
    global $strUserName_Full;
    global $url_me;
    global $pageid;

    // Check authorization
    if ($strUserName != "guest") {
    //
    // Logged in.
    //
        echo <<<EOT
<form enctype="multipart/form-data" action="$url_me" method="post">
    <font class="style2">Привет.<br>Вы вошли как <b>$strUserName_Full</b></font><br>
    <input type="hidden" name="epost" value="logout" />
    <input type="hidden" name="pageid" value="$pageid" />
    <input type="submit" value="Выйти ($strUserName)" />
</form>
EOT;
    } else {
    //
    // Not logged in.
    //
        echo <<<EOT
<!-- Login form -->
<form enctype="multipart/form-data" action="$url_me" method="post">
  <input type="hidden" name="epost" value="login" />
  <input type="hidden" name="pageid" value="$pageid" />
  <input type="hidden" name="mode" value="login" />
  <table>
  <tr>
    <td>Логин:</td>
    <td><input type="text" name="id" /></td>
  </tr><tr>
    <td>Пароль:</td>
    <td><input type="password" name="pass" /></td>
  </tr><tr>
    <td></td><td><input type="submit" value="Войти" /></td>
  </tr>
  </table>
</form>
EOT;
    }
}

?>
