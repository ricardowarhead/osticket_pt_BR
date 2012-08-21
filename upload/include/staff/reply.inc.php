<?php
if(!defined('OSTSCPINC') or !$thisuser->canManageKb()) die('Acesso Negado');
$info=($errors && $_POST)?Format::input($_POST):Format::htmlchars($answer);
if($answer && $_REQUEST['a']!='add'){
    $title='Edite Resposta Pré-determinada';
    $action='update';
}else {
    $title='Adicione Nova Resposta Pré-determinada';
    $action='add';
    $info['isenabled']=1;
}
?>
<div>

    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<div class="msg"><?=$title?></div>
<table width="100%" border="0" cellspacing=1 cellpadding=2>
    <form action="kb.php" method="POST" name="group">
    <input type="hidden" name="a" value="<?=$action?>">
    <input type="hidden" name="id" value="<?=$info['premade_id']?>">
    <tr><td width=80px>Título:</td>
        <td><input type="text" size=45 name="title" value="<?=$info['title']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['title']?></font>
        </td>
    </tr>
    <tr>
        <td>Estado:</td>
        <td>
            <input type="radio" name="isenabled"  value="1"   <?=$info['isenabled']?'checked':''?> /> Ativado
            <input type="radio" name="isenabled"  value="0"   <?=!$info['isenabled']?'checked':''?> />Desativado
            &nbsp;<font class="error">&nbsp;<?=$errors['isenabled']?></font>
        </td>
    </tr>
    <tr><td valign="top">Categoria:</td>
        <td>Departamento onde as respostas serão disponibilizadas.&nbsp;<font class="error">&nbsp;<?=$errors['depts']?></font><br/>
            <select name=dept_id>
                <option value=0 selected>Todos os Departamentos</option>
                <?
                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                while (list($id,$name) = db_fetch_row($depts)){
                    $ck=($info['dept_id']==$id)?'selected':''; ?>
                    <option value="<?=$id?>" <?=$ck?>><?=$name?></option>
                <?
                }?>
            </select>
        </td>
    </tr>
    <tr><td valign="top">Responder:</td>
        <td>Resposta Preparada - variáveis do Tickets de base são suportadas.&nbsp;<font class="error">*&nbsp;<?=$errors['answer']?></font><br/>
            <textarea name="answer" id="answer" cols="90" rows="9" wrap="soft" style="width:80%"><?=$info['answer']?></textarea>
        </td>
    </tr>
    <tr>
        <td nowrap>&nbsp;</td>
        <td><br>
            <input class="button" type="submit" name="submit" value="Aplicar">
            <input class="button" type="reset" name="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="kb.php"'>
        </td>
    </tr>
    </form>
</table>
