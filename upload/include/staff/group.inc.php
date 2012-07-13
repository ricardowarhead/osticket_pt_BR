<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso negado');

$info=($errors && $_POST)?Format::input($_POST):Format::htmlchars($group);
if($group && $_REQUEST['a']!='new'){
    $title='Editar Group: '.$group['group_name'];
    $action='update';
}else {
    $title='Adicionar novo grupo';
    $action='create';
    $info['group_enabled']=isset($info['group_enabled'])?$info['group_enabled']:1; //Default to active 
}

?>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
 <form action="admin.php" method="POST" name="group">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type="hidden" name="t" value="groups">
 <input type="hidden" name="group_id" value="<?=$info['group_id']?>">
 <input type="hidden" name="old_name" value="<?=$info['group_name']?>">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2><?=Format::htmlchars($title)?></td></tr>
        <tr class="subheader"><td colspan=2>
            As configurações de permissões de grupo se aplicam entre os membros do grupo, mas não se aplicam aos administradores e gerentes de grupo em alguns casos.
            </td></tr>
        <tr><th>Nome do grupo:</th>
            <td><input type="text" name="group_name" size=25 value="<?=$info['group_name']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['group_name']?></font>
                    
            </td>
        </tr>
        <tr>
            <th>Status do grupo:</th>
            <td>
                <input type="radio" name="group_enabled"  value="1"   <?=$info['group_enabled']?'checked':''?> /> Active
                <input type="radio" name="group_enabled"  value="0"   <?=!$info['group_enabled']?'checked':''?> />Disabled
                &nbsp;<font class="error">&nbsp;<?=$errors['group_enabled']?></font>
            </td>
        </tr>
        <tr><th valign="top"><br>Acesso ao departamento</th>
            <td class="mainTableAlt"><i>A seleção de membros para o grupo de departamentos são permitidos para acesso na adicão em seu próprio departamento.</i>
                &nbsp;<font class="error">&nbsp;<?=$errors['depts']?></font><br/>
                <?
                //Try to save the state on error...
                $access=($_POST['depts'] && $errors)?$_POST['depts']:explode(',',$info['dept_access']);
                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                while (list($id,$name) = db_fetch_row($depts)){
                    $ck=($access && in_array($id,$access))?'checked':''; ?>
                    <input type="checkbox" name="depts[]" value="<?=$id?>" <?=$ck?> > <?=$name?><br/>
                <?
                }?>
                <a href="#" onclick="return select_all(document.forms['group'])">Select All</a>&nbsp;&nbsp;
                <a href="#" onclick="return reset_all(document.forms['group'])">Select None</a>&nbsp;&nbsp; 
            </td>
        </tr>
        <tr><th>Para <b>Criar</b> Tickets</th>
            <td>
                <input type="radio" name="can_create_tickets"  value="1"   <?=$info['can_create_tickets']?'checked':''?> />Sim 
                <input type="radio" name="can_create_tickets"  value="0"   <?=!$info['can_create_tickets']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Capacidade de abrir tickets em nome de usuários!</i>
            </td>
        </tr>
        <tr><th>Para <b>Editar</b> Tickets</th>
            <td>
                <input type="radio" name="can_edit_tickets"  value="1"   <?=$info['can_edit_tickets']?'checked':''?> />Sim
                <input type="radio" name="can_edit_tickets"  value="0"   <?=!$info['can_edit_tickets']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Capacidade de editar tickets. Administradores & Gerentes de Departamento são permitidos por padrão.</i>
            </td>
        </tr>
        <tr><th>Para <b>Fechar</b> Tickets</th>
            <td>
                <input type="radio" name="can_close_tickets"  value="1" <?=$info['can_close_tickets']?'checked':''?> />Sim
                <input type="radio" name="can_close_tickets"  value="0" <?=!$info['can_close_tickets']?'checked':''?> />Não
                &nbsp;&nbsp;<i><b>Somente fechar em massa:</b> Atendente poderá fechar um ticket durante um período quando definido como NÃO</i>
            </td>
        </tr>
        <tr><th>Para <b>Transferir</b> Tickets</th>
            <td>
                <input type="radio" name="can_transfer_tickets"  value="1" <?=$info['can_transfer_tickets']?'checked':''?> />Sim
                <input type="radio" name="can_transfer_tickets"  value="0" <?=!$info['can_transfer_tickets']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Capacidade para transferir tickets de um departamento para outro.</i>
            </td>
        </tr>
        <tr><th>Para <b>Excluir</b> Tickets</th>
            <td>
                <input type="radio" name="can_delete_tickets"  value="1"   <?=$info['can_delete_tickets']?'checked':''?> />Sim
                <input type="radio" name="can_delete_tickets"  value="0"   <?=!$info['can_delete_tickets']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Tickets excluídos não podem ser recuperados!</i>
            </td>
        </tr>
        <tr><th>Para Banir/Proibir E-mails</th>
            <td>
                <input type="radio" name="can_ban_emails"  value="1" <?=$info['can_ban_emails']?'checked':''?> />Sim
                <input type="radio" name="can_ban_emails"  value="0" <?=!$info['can_ban_emails']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Capacidade para adicionar/remover da lista de banidos/proibidos via interface ticket.</i>
            </td>
        </tr>
        <tr><th>Para gerenciamento preparado</th>
            <td>
                <input type="radio" name="can_manage_kb"  value="1" <?=$info['can_manage_kb']?'checked':''?> />Sim
                <input type="radio" name="can_manage_kb"  value="0" <?=!$info['can_manage_kb']?'checked':''?> />Não
                &nbsp;&nbsp;<i>Capacidade para adicionar/atualizar/desativar/excluir respostas preparadas.</i>
            </td>
        </tr>
    </table>
    <tr><td style="padding-left:165px;padding-top:20px;">
        <input class="button" type="submit" name="submit" value="Submit">
        <input class="button" type="reset" name="reset" value="Reset">
        <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="admin.php?t=groups"'>
        </td>
    </tr>
 </form>
</table>
