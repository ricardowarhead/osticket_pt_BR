<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso Negado');
$info=null;
if($dept && $_REQUEST['a']!='new'){
    //Editing Department.
    $title='Atualizar Departamento';
    $action='update';
    $info=$dept->getInfo();
}else {
    $title='Novo Departamento';
    $action='create';
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
}
$info=($errors && $_POST)?Format::input($_POST):Format::htmlchars($info);

?>
<div class="msg"><?=$title?></div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
 <form action="admin.php?t=dept&id=<?=$info['dept_id']?>" method="POST" name="dept">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type="hidden" name="t" value="dept">
 <input type="hidden" name="dept_id" value="<?=$info['dept_id']?>">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Departamento</td></tr>
        <tr class="subheader"><td colspan=2 >Departamento depende do e-mail &amp; configurações de tópicos de ajuda para tickets de entrada.</td></tr>
        <tr><th>Nome do departamento:</th>
            <td><input type="text" name="dept_name" size=25 value="<?=$info['dept_name']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['dept_name']?></font>
                    
            </td>
        </tr>
        <tr>
            <th>E-mail do Departamento:</th>
            <td>
                <select name="email_id">
                    <option value="">Selecionar um</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name,smtp_active FROM '.EMAIL_TABLE);
                    while (list($id,$email,$name,$smtp) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        if($smtp)
                            $email.=' (SMTP)';
                        ?>
                     <option value="<?=$id?>"<?=($info['email_id']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?=$errors['email_id']?></font>&nbsp;(e-mail enviado)
            </td>
        </tr>    
        <? if($info['dept_id']) { //update 
            $users= db_query('SELECT staff_id,CONCAT_WS(" ",firstname,lastname) as name FROM '.STAFF_TABLE.' WHERE dept_id='.db_input($info['dept_id']));
            ?>
        <tr>
            <th>Gerenciamento de departamento:</th>
            <td>
                <?if($users && db_num_rows($users)) {?>
                <select name="manager_id">
                    <option value=0 >-------Nenhum-------</option>
                    <option value=0 disabled >Seleção de gerenciador (opcional)</option>
                     <?
                     while (list($id,$name) = db_fetch_row($users)){ ?>
                        <option value="<?=$id?>"<?=($info['manager_id']==$id)?'selected':''?>><?=$name?></option>
                     <?}?>
                     
                </select>
                 <?}else {?>
                       Sem usuários (adicionar usuários)
                       <input type="hidden" name="manager_id"  value="0" />
                 <?}?>
                    &nbsp;<font class="error">&nbsp;<?=$errors['manager_id']?></font>
            </td>
        </tr>
        <?}?>
        <tr><th>Tipo do departamento</th>
            <td>
                <input type="radio" name="ispublic"  value="1"   <?=$info['ispublic']?'checked':''?> />Público
                <input type="radio" name="ispublic"  value="0"   <?=!$info['ispublic']?'checked':''?> />Privado (Escondido)
                &nbsp;<font class="error"><?=$errors['ispublic']?></font>
            </td>
        </tr>
        <tr>
            <th valign="top"><br/>Assinatura do departamento:</th>
            <td>
                <i>Required when Dept is public</i>&nbsp;&nbsp;&nbsp;<font class="error"><?=$errors['dept_signature']?></font><br/>
                <textarea name="dept_signature" cols="21" rows="5" style="width: 60%;"><?=$info['dept_signature']?></textarea>
                <br>
                <input type="checkbox" name="can_append_signature" <?=$info['can_append_signature'] ?'checked':''?> > 
                can be appended to responses.&nbsp;(available as a choice for public departments)  
            </td>
        </tr>
        <tr><th>Modelos de e-mail:</th>
            <td>
                <select name="tpl_id">
                    <option value=0 disabled>Selecionar modelo</option>
                    <option value="0" selected="selected">System Default</option>
                    <?
                    $templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id!='.db_input($cfg->getDefaultTemplateId()));
                    while (list($id,$name) = db_fetch_row($templates)){
                        $selected = ($info['tpl_id']==$id)?'SELECTED':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=Format::htmlchars($name)?></option>
                    <?
                    }?>
                </select><font class="error">&nbsp;<?=$errors['tpl_id']?></font><br/>
                <i>Utilizado para e-mails de saída, alertas e notícias para o usuário e atendentes relacionados.</i>
            </td>
        </tr>
        <tr class="header"><td colspan=2>Resposta automática</td></tr>
        <tr class="subheader"><td colspan=2>
            Configurações de auto-resposta globais na seção de preferência devem estar habilitadas para Dept 'Ativar' para que a configuração tenha efeito.
            </td>
        </tr>
        <tr><th>Novo Ticket:</th>
            <td>
                <input type="radio" name="ticket_auto_response"  value="1"   <?=$info['ticket_auto_response']?'checked':''?> />Ativar
                <input type="radio" name="ticket_auto_response"  value="0"   <?=!$info['ticket_auto_response']?'checked':''?> />Desativar
            </td>
        </tr>
        <tr><th>Nova Mensagem:</th>
            <td>
                <input type="radio" name="message_auto_response"  value="1"   <?=$info['message_auto_response']?'checked':''?> />Ativar
                <input type="radio" name="message_auto_response"  value="0"   <?=!$info['message_auto_response']?'checked':''?> />Desativar
            </td>
        </tr>
        <tr>
            <th>Resposta automática do e-mail:</th>
            <td>
                <select name="autoresp_email_id">
                    <option value="0" disabled>Selecionar um</option>
                    <option value="0" selected="selected">E-mail do departamento (acima)</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name,smtp_active FROM '.EMAIL_TABLE.' WHERE email_id!='.db_input($info['email_id']));
                    if($emails && db_num_rows($emails)) {
                        while (list($id,$email,$name,$smtp) = db_fetch_row($emails)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            if($smtp)
                                $email.=' (SMTP)';
                            ?>
                            <option value="<?=$id?>"<?=($info['autoresp_email_id']==$id)?'selected':''?>><?=$email?></option>
                        <?
                        }
                    }?>
                 </select>
                 &nbsp;<font class="error">&nbsp;<?=$errors['autoresp_email_id']?></font>&nbsp;<br/>
                 <i>Endereço de e-mail usado para enviar respostas automáticas, se habilitado.</i>
            </td>
        </tr>
    </table>
    </td></tr>
    <tr><td style="padding:10px 0 10px 200px;">
        <input class="button" type="submit" name="submit" value="Submit">
        <input class="button" type="reset" name="reset" value="Reset">
        <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="admin.php?t=dept"'>
    </td></tr>
    </form>
</table>
