<?php
if(!defined('OSTADMININC') || basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki? '); //Say hi to our friend..
if(!$thisuser || !$thisuser->isadmin()) die('Acesso Negado');

$info=($_POST && $errors)?Format::input($_POST):Format::htmlchars($cfg->getSMTPInfo());
?>
<div class="msg"><?=$title?></div>
<table width="98%" border="0" cellspacing=0 cellpadding=0>
<form action="admin.php?t=smtp" method="post">
 <input type="hidden" name="do" value="salvar">
 <input type="hidden" name="t" value="smtp">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Configuração do Servidor SMTP (Opcional)</b></td></tr>
        <tr class="subheader"><td colspan=2>
             Quando ativado o sistema irá utilizar um servidor SMTP em vez do email PHP () para emails enviados.<br>
             Deixe o nome de usuário e senha vazia para o servidor SMTP não exigir autenticação.<br/>
            <b>Por favor, seja paciente, o sistema irá tentar efetuar login no servidor SMTP para validar a informação de login.</b></td></tr>
        <tr><th>Permitir SMTP</th>
            <td>
                <input type="radio" name="isenabled"  value="1"   <?=$info['isenabled']?'checked':''?> /><b>Sim</b>
                <input type="radio" name="isenabled"  value="0"   <?=!$info['isenabled']?'checked':''?> />Não
                &nbsp;<font class="error">&nbsp;<?=$errors['isenabled']?></font>
            </td>
        </tr>
        <tr><th>Host SMPT</th>
            <td><input type="text" name="host" size=35 value="<?=$info['host']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['host']?></font>
            </td>
        </tr>
        <tr><th>Porta SMTP</th>
            <td><input type="text" name="port" size=6 value="<?=$info['port']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['port']?></font>
            </td>
        </tr>
        <tr><th>Criptografia</th>
            <td>
                 <input type="radio" name="issecure"  value="0"  
                    <?=!$info['issecure']?'checked':''?> />None
                 <input type="radio" name="issecure"  value="1"   
                    <?=$info['issecure']?'checked':''?> />TLS (secure)
                <font class="error">&nbsp;<?=$errors['issecure']?></font>
            </td>
        </tr>
        <tr><th>Nomde de Usuário</th>
            <td class="mainTableAlt"><input type="text" name="userid" size=35 value="<?=$info['userid']?>" autocomplete='off' >
                &nbsp;<font class="error">*&nbsp;<?=$errors['userid']?></font>
            </td>
        </tr>
        <tr><th>Senha</th>
            <td><input type="password" name="userpass" size=35 value="<?=$info['userpass']?>" autocomplete='off'>
                &nbsp;<font class="error">*&nbsp;<?=$errors['userpass']?></font>
            </td>
        </tr>
        <tr><th>Email</th>
            <td>
                <input type="text" name="fromaddress" size=30 value="<?=$info['fromaddress']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['fromaddress']?></font>
            </td>
        </tr>
        <tr><th>Nome do Email:</th>
            <td>
                <input type="text" name="fromname" size=30 value="<?=$info['fromname']?>">&nbsp;<font class="error">&nbsp;<?=$errors['fromname']?></font>
                &nbsp;&nbsp;(<i>Nome para email opcional.</i>)
            </td>
        </tr>
    </table>
   </td></tr>
   <tr><td style="padding:10px 0 10px 220px;">
            <input class="button" type="submit" name="submit" value="Aplicar">
            <input class="button" type="reset" name="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="admin.php?t=email"'>
        </td>
     </tr>
</form>
</table>
