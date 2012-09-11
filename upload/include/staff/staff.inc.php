<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Access Denied');

$rep=null;
$newuser=true;
if($staff && $_REQUEST['a']!='new'){
    $rep=$staff->getInfo();
    $title='Atualizar: '.$rep['firstname'].' '.$rep['lastname'];
    $action='update';
    $pwdinfo='Para redefinir a senha digite uma nova abaixo';
    $newuser=false;
}else {
    $title='Novo Membro';
    $pwdinfo='Necessita de senha temporária';
    $action='create';
    $rep['resetpasswd']=isset($rep['resetpasswd'])?$rep['resetpasswd']:1;
    $rep['isactive']=isset($rep['isactive'])?$rep['isactive']:1;
    $rep['dept_id']=$rep['dept_id']?$rep['dept_id']:$_GET['dept'];
    $rep['isvisible']=isset($rep['isvisible'])?$rep['isvisible']:1;
}
$rep=($errors && $_POST)?Format::input($_POST):Format::htmlchars($rep);

//get the goodies.
$groups=db_query('SELECT group_id,group_name FROM '.GROUP_TABLE);
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE);

?>
<div class="msg"><?=$title?></div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
<form action="admin.php" method="post">
 <input type="hidden" name="do" value="<?=$action?>">
 <input type="hidden" name="a" value="<?=Format::htmlchars($_REQUEST['a'])?>">
 <input type="hidden" name="t" value="staff">
 <input type="hidden" name="staff_id" value="<?=$rep['staff_id']?>">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Usuário</td></tr>
        <tr class="subheader"><td colspan=2>Informações</td></tr>
        <tr>
            <th>Nome de usuário:</th>
            <td><input type="text" name="username" value="<?=$rep['username']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['username']?></font></td>
        </tr>
        <tr>
            <th>Departamento:</th>
            <td>
                <select name="dept_id">
                    <option value=0>Selecione Departamento</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                        $selected = ($rep['dept_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?> Departamento</option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['dept']?></font>
            </td>
        </tr>
        <tr>
            <th>Grupo do usuário:</th>
            <td>
                <select name="group_id">
                    <option value=0>Selecione Grupo</option>
                    <?
                    while (list($id,$name) = db_fetch_row($groups)){
                        $selected = ($rep['group_id']==$id)?'selected':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['group']?></font>
            </td>
        </tr>
        <tr>
            <th>Nome (Primeiro,Último):</th>
            <td>
                <input type="text" name="firstname" value="<?=$rep['firstname']?>">&nbsp;<font class="error">*</font>
                &nbsp;&nbsp;&nbsp;<input type="text" name="lastname" value="<?=$rep['lastname']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['name']?></font></td>
        </tr>
        <tr>
            <th>Email:</th>
            <td><input type="text" name="email" size=25 value="<?=$rep['email']?>">
                &nbsp;<font class="error">*&nbsp;<?=$errors['email']?></font></td>
        </tr>
        <tr>
            <th>Telefone do Escritório:</th>
            <td>
                <input type="text" name="phone" value="<?=$rep['phone']?>" >&nbsp;Ramal&nbsp;
                <input type="text" name="phone_ext" size=6 value="<?=$rep['phone_ext']?>" >
                    &nbsp;<font class="error">&nbsp;<?=$errors['phone']?></font></td>
        </tr>
        <tr>
            <th>Telefone Celular:</th>
            <td>
                <input type="text" name="mobile" value="<?=$rep['mobile']?>" >
                    &nbsp;<font class="error">&nbsp;<?=$errors['mobile']?></font></td>
        </tr>
        <tr>
            <th valign="top">Assinatura:</th>
            <td><textarea name="signature" cols="21" rows="5" style="width: 60%;"><?=$rep['signature']?></textarea></td>
        </tr>
        <tr>
            <th>Senha:</th>
            <td>
                <i><?=$pwdinfo?></i>&nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?=$errors['npassword']?></font> <br/>
                <input type="password" name="npassword" AUTOCOMPLETE=OFF >&nbsp;
            </td>
        </tr>
        <tr>
            <th>Confirmação de senha:</th>
            <td class="mainTableAlt"><input type="password" name="vpassword" AUTOCOMPLETE=OFF >
                &nbsp;<font class="error">&nbsp;<?=$errors['vpassword']?></font></td>
        </tr>
        <tr>
            <th>Troca de senha forçada:</th>
            <td>
                <input type="checkbox" name="resetpasswd" <?=$rep['resetpasswd'] ? 'checked': ''?>>Exigida a mudança de senha no próximo login</td>
        </tr>
        <tr class="header"><td colspan=2>Permissão da conta, Configurações &amp; de estado</td></tr>
        <tr class="subheader"><td colspan=2>
            As permissões do atendente também são baseadas no seu grupo. <b>Administrador não é limitado por configurações do grupo.</b></td>
        </tr> 
        <tr><th><b>Estado da Conta</b></th>
            <td>
                        <input type="radio" name="isactive"  value="1" <?=$rep['isactive']?'checked':''?> /><b>Ativar</b>
                        <input type="radio" name="isactive"  value="0" <?=!$rep['isactive']?'checked':''?> /><b>Travado</b>
                        &nbsp;&nbsp;
            </td>
        </tr>
        <tr><th><b>Tipo da Conta</b></th>
            <td class="mainTableAlt">
                        <input type="radio" name="isadmin"  value="1" <?=$rep['isadmin']?'checked':''?> /><font color="red"><b>Administrador</b></font>
                        <input type="radio" name="isadmin"  value="0" <?=!$rep['isadmin']?'checked':''?> /><b>Atendente</b>
                        &nbsp;&nbsp;
            </td>
        </tr>
        <tr><th>Listagem de comitiva</th>
            <td>
               <input type="checkbox" name="isvisible" <?=$rep['isvisible'] ? 'checked': ''?>>Motrar o usuário na lista de comitiva
            </td>
        </tr>
        <tr><th>Modo Férias</th>
            <td class="mainTableAlt">
             <input type="checkbox" name="onvacation" <?=$rep['onvacation'] ? 'checked': ''?>>
                Atendente de férias. (<i>Sem alertas ou tickets atribuídos</i>)
                &nbsp;<font class="error">&nbsp;<?=$errors['vacation']?></font>
            </td>
        </tr>
    </table>
   </td></tr>
   <tr><td style="padding:5px 0 10px 210px;">
        <input class="button" type="submit" name="submit" value="Aplicar">
        <input class="button" type="reset" name="reset" value="Redefinir">
        <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="admin.php?t=staff"'>
    </td></tr>
  </form>
</table>
