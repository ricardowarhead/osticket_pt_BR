<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso Negado');


$info['phrase']=($errors && $_POST['phrase'])?Format::htmlchars($_POST['phrase']):$cfg->getAPIPassphrase();
$select='SELECT * ';
$from='FROM '.API_KEY_TABLE;
$where='';
$sortOptions=array('date'=>'created','ip'=>'ipaddr');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
//Sorting options...
if($_REQUEST['sort']) {
    $order_column =$sortOptions[$_REQUEST['sort']];
}

if($_REQUEST['order']) {
    $order=$orderWays[$_REQUEST['order']];
}
$order_column=$order_column?$order_column:'ipaddr';
$order=$order?$order:'ASC';
$order_by=" ORDER BY $order_column $order ";

$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=1000;//No limit.
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('admin.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
$query="$select $from $where $order_by";
//echo $query;
$result = db_query($query);
$showing=db_num_rows($result)?$pageNav->showing():'';
$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..
$deletable=0;
?>
<div class="msg">Chave API</div>
<hr>
<div><b><?=$showing?></b></div>
 <table width="100%" border="0" cellspacing=1 cellpadding=2>
   <form action="admin.php?t=api" method="POST" name="api" onSubmit="return checkbox_checker(document.forms['api'],1,0);">
   <input type=hidden name='t' value='api'>
   <input type=hidden name='do' value='mass_process'>
   <tr><td>
    <table border="0" cellspacing=0 cellpadding=2 class="dtable" align="center" width="100%">
        <tr>
	        <th width="7px">&nbsp;</th>
	        <th>Chave API</th>
            <th width="10" nowrap>Ativado</th>
            <th width="100" nowrap>&nbsp;&nbsp;Endereço IP</th>
	        <th width="150" nowrap>&nbsp;&nbsp;
                <a href="admin.php?t=api&sort=date&order=<?=$negorder?><?=$qstr?>" title="Ordenar por Criar Data <?=$negorder?>">Criado</a></th>
        </tr>
        <?
        $class = 'row1';
        $total=0;
        $active=$inactive=0;
        $sids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($result && db_num_rows($result)):
            $dtpl=$cfg->getDefaultTemplateId();
            while ($row = db_fetch_array($result)) {
                $sel=false;
                $disabled='';
                if($row['isactive'])
                    $active++;
                else
                    $inactive++;
                    
                if($sids && in_array($row['id'],$sids)){
                    $class="$class highlight";
                    $sel=true;
                }
                ?>
            <tr class="<?=$class?>" id="<?=$row['id']?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?=$row['id']?>" <?=$sel?'checked':''?>
                        onClick="highLight(this.value,this.checked);">
                <td>&nbsp;<?=$row['apikey']?></td>
                <td><?=$row['isactive']?'<b>Sim</b>':'Não'?></td>
                <td>&nbsp;<?=$row['ipaddr']?></td>
                <td>&nbsp;<?=Format::db_datetime($row['created'])?></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //nothin' found!! ?> 
            <tr class="<?=$class?>"><td colspan=5><b>Consulta retornou 0 resultados</b>&nbsp;&nbsp;<a href="admin.php?t=templates">Índice de lista</a></td></tr>
        <?
        endif; ?>
     
     </table>
    </td></tr>
    <?
    if(db_num_rows($result)>0): //Show options..
     ?>
    <tr>
        <td align="center">
            <?php
            if($inactive) {?>
                <input class="button" type="submit" name="enable" value="Ativar"
                     onClick='return confirm("Tem certeza de que deseja ATIVAR as teclas selecionadas?");'>
            <?php
            }
            if($active){?>
            &nbsp;&nbsp;
                <input class="button" type="submit" name="disable" value="Desativar"
                     onClick='return confirm("Tem certeza de que deseja DESATIVAR chaves selecionadas?");'>
            <?}?>
            &nbsp;&nbsp;
            <input class="button" type="submit" name="delete" value="Excluir" 
                     onClick='return confirm("Tem certeza que deseja EXCLUIR chaves selecionadas?");'>
        </td>
    </tr>
    <?
    endif;
    ?>
    </form>
 </table>
 <br/>
 <div class="msg">Adicionar novo IP</div>
 <hr>
 <div>
   Add a new IP address.&nbsp;&nbsp;<font class="error"><?=$errors['ip']?></font>
   <form action="admin.php?t=api" method="POST" >
    <input type=hidden name='t' value='api'>
    <input type=hidden name='do' value='add'>
    New IP:
    <input name="ip" size=30 value="<?=($errors['ip'])?Format::htmlchars($_REQUEST['ip']):''?>" />
    <font class="error">*&nbsp;</font>&nbsp;&nbsp;
     &nbsp;&nbsp; <input class="button" type="submit" name="add" value="Adicionar">
    </form>
 </div>
 <br/>
 <div class="msg">Frase Secreta API</div>
 <hr>
 <div>
   Frase secreta deve ter pelo menos 3 palavras. Necessária para gerar as chaves de API.<br/>
   <form action="admin.php?t=api" method="POST" >
    <input type=hidden name='t' value='api'>
    <input type=hidden name='do' value='update_phrase'>
    Frase:
    <input name="phrase" size=50 value="<?=Format::htmlchars($info['phrase'])?>" />
    <font class="error">*&nbsp;<?=$errors['phrase']?></font>&nbsp;&nbsp;
     &nbsp;&nbsp; <input class="button" type="submit" name="update" value="Alterar">
    </form>
    <br/><br/>
    <div><i>Por favor, note que a mudança da fase passe não invalida as chaves existentes. Para gerar uma chave precisa de apagar e re adicioná-la.</i></div>
 </div>
