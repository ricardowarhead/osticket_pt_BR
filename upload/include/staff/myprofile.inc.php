<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Kwaheri');

?>
<div class="msg">Minhas informações</div>
<table width="100%" border="0" cellspacing=0 cellpadding=2>
 <form action="profile.php" method="post">
 <input type="hidden" name="t" value="info">
 <input type="hidden" name="id" value="<?=$thisuser->getId()?>">
    <tr>
        <td width="110"><b>Nome de usuário:</b></td>
        <td>&nbsp;<?=$thisuser->getUserName()?></td>
    </tr>
    <tr>
        <td>Primeiro Nome:</td>
        <td><input type="text" name="firstname" value="<?=$rep['firstname']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['firstname']?></font></td>
    </tr>
    <tr>
        <td>Último Nome:</td>
        <td><input type="text" name="lastname" value="<?=$rep['lastname']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['lastname']?></font></td>
    </tr>
    <tr>
        <td>Email:</td>
        <td><input type="text" name="email" size=25 value="<?=$rep['email']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['email']?></font></td>
    </tr>
    <tr>
        <td>Telefone do Escritório:</td>
        <td>
            <input type="text" name="phone" value="<?=$rep['phone']?>" ><font class="error">&nbsp;<?=$errors['phone']?></font>&nbsp;Ramal&nbsp;
            <input type="text" name="phone_ext" size=6 value="<?=$rep['phone_ext']?>" >
            <font class="error">&nbsp;<?=$errors['phone_ext']?></font>
        </td>
    </tr>
    <tr>
        <td>Celular:</td>
        <td><input type="text" name="mobile" value="<?=$rep['mobile']?>" >
            &nbsp;<font class="error">&nbsp;<?=$errors['mobile']?></font></td>
    </tr>
    <tr>
        <td valign="top">Assinatura:</td>
        <td><textarea name="signature" cols="21" rows="5" style="width: 60%;"><?=$rep['signature']?></textarea></td>
    </tr>
    <tr><td>&nbsp;</td>
        <td> <br/>
            <input class="button" type="submit" name="submit" value="Salvar">
            <input class="button" type="reset" name="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="index.php"'>
        </td>
    </tr>
 </form>
</table> 