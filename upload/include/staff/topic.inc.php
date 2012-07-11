<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Access Denied');

$info=($_POST && $errors)?Format::input($_POST):array(); //Re-use the post info on error...savekeyboards.org
if($topic && $_REQUEST['a']!='new'){
    $title='Edit Topic';
    $action='update';
    $info=$info?$info:$topic->getInfo();
}else {
   $title='New Help Topic';
   $action='create';
   $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
}
//get the goodies.
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);
$priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
?>
<form action="admin.php?t=topics" method="post">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type='hidden' name='t' value='topics'>
 <input type="hidden" name="topic_id" value="<?=$info['topic_id']?>">
<table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
    <tr class="header"><td colspan=2><?=$title?></td></tr>
    <tr class="subheader">
        <td colspan=2 >Desativar resposta automática irá substituir as configurações de departamento.</td>
    </tr>
    <tr>
        <th width="20%">Tópico de Ajuda:</th>
        <td><input type="text" name="topic" size=45 value="<?=$info['topic']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['topic']?></font></td>
    </tr>
    <tr><th>Status do Tópico</th>
        <td>
            <input type="radio" name="isactive"  value="1"   <?=$info['isactive']?'checked':''?> />Ativado
            <input type="radio" name="isactive"  value="0"   <?=!$info['isactive']?'checked':''?> />Desativado
        </td>
    </tr>
    <tr>
        <th nowrap>Resposta Automática:</th>
        <td>
            <input type="checkbox" name="noautoresp" value=1 <?=$info['noautoresp']? 'checked': ''?> >
                <b>Desativado</b> resposta automática para este tópico.   (<i>Substituir configuração de Dept</i>)
        </td>
    </tr>
    <tr>
        <th>Prioridade do novo Ticket:</th>
        <td>
            <select name="priority_id">
                <option value=0>Selecione Prioridade</option>
                <?
                while (list($id,$name) = db_fetch_row($priorities)){
                    $selected = ($info['priority_id']==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                <?
                }?>
            </select>&nbsp;<font class="error">*&nbsp;<?=$errors['priority_id']?></font>
        </td>
    </tr>
    <tr>
        <th nowrap>Departamento do novo Ticket:</th>
        <td>
            <select name="dept_id">
                <option value=0>Selecione Departamento</option>
                <?
                while (list($id,$name) = db_fetch_row($depts)){
                    $selected = ($info['dept_id']==$id)?'selected':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?> Dept</option>
                <?
                }?>
            </select>&nbsp;<font class="error">*&nbsp;<?=$errors['dept_id']?></font>
        </td>
    </tr>
</table>
<div style="padding-left:220px;">
    <input class="button" type="submit" name="submit" value="Submit">
    <input class="button" type="reset" name="reset" value="Reset">
    <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="admin.php?t=topics"'>
</div>
</form>
