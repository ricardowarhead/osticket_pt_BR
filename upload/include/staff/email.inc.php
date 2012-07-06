<?php
if(!defined('OSTADMININC') || basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Habari/Jambo rafiki? '); //Say hi to our friend..
if(!$thisuser || !$thisuser->isadmin()) die('Access Denied');

$info=($_POST && $errors)?$_POST:array(); //Re-use the post info on error...savekeyboards.org
if($email && $_REQUEST['a']!='new'){
    $title='Edit Email'; 
    $action='update';
    if(!$info) {
        $info=$email->getInfo();
        $info['userpass']=$info['userpass']?Misc::decrypt($info['userpass'],SECRET_SALT):'';
    }
    $qstr='?t=email&id='.$email->getId();
}else {
   $title='New Email';
   $action='create';
   $info['smtp_auth']=isset($info['smtp_auth'])?$info['smtp_auth']:1;
}

$info=Format::htmlchars($info);
//get the goodies.
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);
$priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
?>
<div class="msg"><?=$title?></div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
<form action="admin.php<?=$qstr?>" method="post">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type="hidden" name="t" value="email">
 <input type="hidden" name="email_id" value="<?=$info['email_id']?>">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Informações do e-mail</td></tr>
        <tr class="subheader">
            <td colspan=2 >As configurações são principalmente para tickets por e-mail. Para tickets on-line/web, consulte os tópicos de ajuda.</td>
        </tr>
        <tr><th>Endereço de e-mail</th>
            <td>
                <input type="text" name="email" size=30 value="<?=$info['email']?>">&nbsp;<font class="error">*&nbsp;<?=$errors['email']?></font>
            </td>
        </tr>
        <tr><th>Nome do e-mail:</th>
            <td>
                <input type="text" name="name" size=30 value="<?=$info['name']?>">&nbsp;<font class="error">&nbsp;<?=$errors['name']?></font>
                &nbsp;&nbsp;(<i>Optional email's FROM name.</i>)
            </td>
        </tr>
        <tr><th>Prioridade do novo ticket</th>
            <td>
                <select name="priority_id">
                    <option value=0>Selecionar prioridade</option>
                    <?
                    while (list($id,$name) = db_fetch_row($priorities)){
                        $selected = ($info['priority_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['priority_id']?></font>
            </td>
        </tr>
        <tr><th>Departamento do novo ticket</th>
            <td>
                <select name="dept_id">
                    <option value=0>Selecionar departamento</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                        $selected = ($info['dept_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?> Dept</option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">&nbsp;<?=$errors['dept_id']?></font>&nbsp;
            </td>
        </tr>
        <tr><th>Resposta automática</th>
            <td>
                <input type="checkbox" name="noautoresp" value=1 <?=$info['noautoresp']? 'checked': ''?> ><b>Desabilitada</b> resposta automática para esse e-mail.
                &nbsp;&nbsp;(<i>Sobrescrever configuração de departamento</i>)
            </td>
        </tr>
        <tr class="subheader">
            <td colspan=2 ><b>Informação de login (opcional)</b>: Necessária quando IMAP/POP e/ou SMTP são habilitados.</td>
        </tr>
        <tr><th>Nome do usuário (nickname)</th>
            <td><input type="text" name="userid" size=35 value="<?=$info['userid']?>" autocomplete='off' >
                &nbsp;<font class="error">&nbsp;<?=$errors['userid']?></font>
            </td>
        </tr>
        <tr><th>Senha</th>
            <td>
               <input type="password" name="userpass" size=35 value="<?=$info['userpass']?>" autocomplete='off'>
                &nbsp;<font class="error">&nbsp;<?=$errors['userpass']?></font>
            </td>
        </tr>
        <tr class="header"><td colspan=2>Conta de e-mail (opcional)</b></td></tr>
        <tr class="subheader"><td colspan=2>
             Configuração para buscar e-mails recebidos. A busca de e-mail deve ser habilitada com o autocron ativo ou configurar cron externamente.<br>
            <b>Por favor, seja paciente, o sistema vai tentar entrar no servidor de email para validar a informação de entrada de login.</b>
            <font class="error">&nbsp;<?=$errors['mail']?></font></td></tr>
        <tr><th>Status</th>
            <td>
                <label><input type="radio" name="mail_active"  value="1"   <?=$info['mail_active']?'checked':''?> />Enable</label>
                <label><input type="radio" name="mail_active"  value="0"   <?=!$info['mail_active']?'checked':''?> />Disable</label>
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_active']?></font>
            </td>
        </tr>
        <tr><th>Host</th>
            <td><input type="text" name="mail_host" size=35 value="<?=$info['mail_host']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_host']?></font>
            </td>
        </tr>
        <tr><th>Porta</th>
            <td><input type="text" name="mail_port" size=6 value="<?=$info['mail_port']?$info['mail_port']:''?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_port']?></font>
            </td>
        </tr>
        <tr><th>Protocolo</th>
            <td>
                <select name="mail_protocol">
                    <option value='POP'>Select</option>
                    <option value='POP' <?=($info['mail_protocol']=='POP')?'selected="selected"':''?> >POP</option>
                    <option value='IMAP' <?=($info['mail_protocol']=='IMAP')?'selected="selected"':''?> >IMAP</option>
                </select>
                <font class="error">&nbsp;<?=$errors['mail_protocol']?></font>
            </td>
        </tr>

        <tr><th>Criptografia</th>
            <td>
                 <label><input type="radio" name="mail_encryption"  value="NONE"
                    <?=($info['mail_encryption']!='SSL')?'checked':''?> />None</label>
                 <label><input type="radio" name="mail_encryption"  value="SSL"
                    <?=($info['mail_encryption']=='SSL')?'checked':''?> />SSL</label>
                <font class="error">&nbsp;<?=$errors['mail_encryption']?></font>
            </td>
        </tr>
        <tr><th>Frequencia de busca</th>
            <td>
                <input type="text" name="mail_fetchfreq" size=4 value="<?=$info['mail_fetchfreq']?$info['mail_fetchfreq']:''?>"> Delay intervals in minutes
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_fetchfreq']?></font>
            </td>
        </tr>
        <tr><th>Máximo de e-mails por busca</th>
            <td>
                <input type="text" name="mail_fetchmax" size=4 value="<?=$info['mail_fetchmax']?$info['mail_fetchmax']:''?>"> Maximum emails to process per fetch.
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_fetchmax']?></font>
            </td>
        </tr>
        <tr><th>Apagar mensagens</th>
            <td>
                <input type="checkbox" name="mail_delete" value=1 <?=$info['mail_delete']? 'checked': ''?> >
                    Delete fetched message(s) (<i>recomendado quando usar POP</i>)
                &nbsp;<font class="error">&nbsp;<?=$errors['mail_delete']?></font>
            </td>
        </tr>
        <tr class="header"><td colspan=2>Configurações SMTP (opcional)</b></td></tr>
        <tr class="subheader"><td colspan=2>
             Quando habilitar o <b>email account</b> vai usar o servidor SMTP ao invés de usar a função interna mail() do PHP para saída de e-mails.<br>
            <b>Please be patient, the system will try to login to SMTP server to validate the entered login info.</b>
                <font class="error">&nbsp;<?=$errors['smtp']?></font></td></tr>
        <tr><th>Status</th>
            <td>
                <label><input type="radio" name="smtp_active"  value="1"   <?=$info['smtp_active']?'checked':''?> />Enable</label>
                <label><input type="radio" name="smtp_active"  value="0"   <?=!$info['smtp_active']?'checked':''?> />Disable</label>
                &nbsp;<font class="error">&nbsp;<?=$errors['smtp_active']?></font>
            </td>
        </tr>
        <tr><th>SMTP Host</th>
            <td><input type="text" name="smtp_host" size=35 value="<?=$info['smtp_host']?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['smtp_host']?></font>
            </td>
        </tr>
        <tr><th>SMTP Port</th>
            <td><input type="text" name="smtp_port" size=6 value="<?=$info['smtp_port']?$info['smtp_port']:''?>">
                &nbsp;<font class="error">&nbsp;<?=$errors['smtp_port']?></font>
            </td>
        </tr>
        <tr><th>Authentication Required?</th>
            <td>

                 <label><input type="radio" name="smtp_auth"  value="1"
                    <?=$info['smtp_auth']?'checked':''?> />Yes</label>
                 <label><input type="radio" name="smtp_auth"  value="0"
                    <?=!$info['smtp_auth']?'checked':''?> />NO</label>
                <font class="error">&nbsp;<?=$errors['smtp_auth']?></font>
            </td>
        </tr>
        <tr><th>Encryption</th>
            <td>Best available authentication method is auto-selected based on what the sever supports.</td>
        </tr>
    </table>
   </td></tr>
   <tr><td style="padding:10px 0 10px 220px;">
            <input class="button" type="submit" name="submit" value="Submit">
            <input class="button" type="reset" name="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="admin.php?t=email"'>
        </td>
     </tr>
</form>
</table>
