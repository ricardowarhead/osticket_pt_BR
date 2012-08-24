<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso Negado');
//List all Depts
$sql='SELECT dept.dept_id,dept_name,email.email_id,email.email,email.name as email_name,ispublic,count(staff.staff_id) as users '.
     ',CONCAT_WS(" ",mgr.firstname,mgr.lastname) as manager,mgr.staff_id as manager_id,dept.created,dept.updated  FROM '.DEPT_TABLE.' dept '.
     ' LEFT JOIN '.STAFF_TABLE.' mgr ON dept.manager_id=mgr.staff_id '.
     ' LEFT JOIN '.EMAIL_TABLE.' email ON dept.email_id=email.email_id '.
     ' LEFT JOIN '.STAFF_TABLE.' staff ON dept.dept_id=staff.dept_id ';
$depts=db_query($sql.' GROUP BY dept.dept_id ORDER BY dept_name');    
?>
<div class="msg">Departamentos</div>
<table width="100%" border="0" cellspacing=1 cellpadding=2>
    <form action="admin.php?t=dept" method="POST" name="depts" onSubmit="return checkbox_checker(document.forms['depts'],1,0);">
    <input type=hidden name='do' value='mass_process'>
    <tr><td>
    <table border="0" cellspacing=0 cellpadding=2 class="dtable" align="center" width="100%">
        <tr>
	        <th width="7px">&nbsp;</th>
	        <th>Nome do departamento</th>
            <th>Tipo</th>
            <th width=10>Usuários</th>
            <th>E-mail principal de saída</th>
            <th>Gerenciamento</th>
        </tr>
        <?
        $class = 'row1';
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($depts && db_num_rows($depts)):
            $defaultId=$cfg->getDefaultDeptId();
            while ($row = db_fetch_array($depts)) {
                $sel=false;
                if(($ids && in_array($row['dept_id'],$ids)) && ($deptID && $deptID==$row['dept_id'])){
                    $class="$class highlight";
                    $sel=true;
                }
                $row['email']=$row['email_name']?($row['email_name'].' &lt;'.$row['email'].'&gt;'):$row['email'];
                $default=($defaultId==$row['dept_id'])?'(Default)':'';
                ?>
            <tr class="<?=$class?>" id="<?=$row['dept_id']?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?=$row['dept_id']?>" <?=$sel?'checked':''?>  <?=$default?'disabled':''?>
                            onClick="highLight(this.value,this.checked);"> </td>
                <td><a href="admin.php?t=dept&id=<?=$row['dept_id']?>"><?=$row['dept_name']?></a>&nbsp;<?=$default?></td>
                <td><?=$row['ispublic']?'Public':'<b>Private</b>'?></td>
                <td>&nbsp;&nbsp;
                    <b>
                    <?if($row['users']>0) {?>
                        <a href="admin.php?t=staff&dept=<?=$row['dept_id']?>"><?=$row['users']?></a>
                    <?}else{?> 0
                    <?}?>
                    </b>
                </td>
                <td><a href="admin.php?t=email&id=<?=$row['email_id']?>"><?=$row['email']?></a></td>
                <td><a href="admin.php?t=staff&id=<?=$row['manager_id']?>"><?=$row['manager']?>&nbsp;</a></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //not tickets found!! ?> 
            <tr class="<?=$class?>"><td colspan=6><b>Retorno de consulta com 0 resultado</b></td></tr>
        <?
        endif; ?>
    </table>
    </td></tr>
    <?
    if($depts && db_num_rows($depts)): //Show options..
     ?>
    <tr>
        <td style="padding-left:20px">
            Selecione:&nbsp;
            <a href="#" onclick="return select_all(document.forms['depts'],true)">Todos</a>&nbsp;&nbsp;
            <a href="#" onclick="return reset_all(document.forms['depts'])">Nenhum</a>&nbsp;&nbsp;
            <a href="#" onclick="return toogle_all(document.forms['depts'],true)">Alternar</a>&nbsp;&nbsp;
        </td>
    </tr>
    <tr>
        <td align="center">
            <input class="button" type="submit" name="public" value="Tornar Público"
                onClick=' return confirm("Tem certeza quem deseja tornar o(s) departamento(s) selecionado(s) público(s)?");'>
            <input class="button" type="submit" name="private" value="Tornar Privado" 
                onClick=' return confirm("Tem certeza quem deseja tornar o(s) departamento(s) selecionado(s) privado(s)?");'>
            <input class="button" type="submit" name="delete" value="Excluir Departamento(s)" 
                onClick=' return confirm(Tem certeza que deseja excluir o(s) departamento(s) selecionado(s)?");'>
        </td>
    </tr>
    <?
    endif;
    ?>
    </form>
</table>
