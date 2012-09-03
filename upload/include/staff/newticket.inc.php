<?php
if(!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Acesso Negado');
$info=($_POST && $errors)?Format::input($_POST):array(); //on error...use the post data
?>
<div width="100%">
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" class="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p class="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<table width="80%" border="0" cellspacing=1 cellpadding=2>
   <form action="tickets.php" method="post" enctype="multipart/form-data">
    <input type='hidden' name='a' value='open'>
    <tr><td align="left" colspan=2>Por favor, preencha o formulário abaixo para abrir um novo ticket.</td></tr>
    <tr>
        <td align="left" nowrap width="20%"><b>Endereço de E-mail:</b></td>
        <td>
            <input type="text" id="email" name="email" size="25" value="<?=$info['email']?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['email']?></font>
            <? if($cfg->notifyONNewStaffTicket()) {?>
               &nbsp;&nbsp;&nbsp;
               <input type="checkbox" name="alertuser" <?=(!$errors || $info['alertuser'])? 'checked': ''?>>Envie o alera para o usuário.
            <?}?>
        </td>
    </tr>
    <tr>
        <td align="left" ><b>Nome Completo:</b></td>
        <td>
            <input type="text" id="name" name="name" size="25" value="<?=$info['name']?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['name']?></font>
        </td>
    </tr>
    <tr>
        <td align="left">Telefone:</td>
        <td><input type="text" name="phone" size="25" value="<?=$info['phone']?>">
            &nbsp;Ext&nbsp;<input type="text" name="phone_ext" size="6" value="<?=$info['phone_ext']?>">
            <font class="error">&nbsp;<?=$errors['phone']?></font></td>
    </tr>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr>
        <td align="left"><b>Origem do Ticket:</b></td>
        <td>
            <select name="source">
                <option value="" selected >Selecionar Origem</option>
                <option value="Phone" <?=($info['source']=='Phone')?'selected':''?>>Phone</option>
                <option value="Email" <?=($info['source']=='Email')?'selected':''?>>Email</option>
                <option value="Other" <?=($info['source']=='Other')?'selected':''?>>Other</option>
            </select>
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['source']?></font>
        </td>
    </tr>
    <tr>
        <td align="left"><b>Departamento:</b></td>
        <td>
            <select name="deptId">
                <option value="" selected >Selecione o Departamento</option>
                <?
                 $services= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name');
                 while (list($deptId,$dept) = db_fetch_row($services)){
                    $selected = ($info['deptId']==$deptId)?'selected':''; ?>
                    <option value="<?=$deptId?>"<?=$selected?>><?=$dept?></option>
                <?
                 }?>
            </select>
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['deptId']?></font>
        </td>
    </tr>
    <tr>
        <td align="left"><b>Assunto:</b></td>
        <td>
            <input type="text" name="subject" size="35" value="<?=$info['subject']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['subject']?></font>
        </td>
    </tr>
    <tr>
        <td align="left" valign="top"><b>Resumo do Problema:</b></td>
        <td>
            <i>Visível para o cliente.</i><font class="error"><b>*&nbsp;<?=$errors['issue']?></b></font><br/>
            <?
            $sql='SELECT premade_id,title FROM '.KB_PREMADE_TABLE.' WHERE isenabled=1';
            $canned=db_query($sql);
            if($canned && db_num_rows($canned)) {
            ?>
             Pré Definições:&nbsp;
              <select id="canned" name="canned"
                onChange="getCannedResponse(this.options[this.selectedIndex].value,this.form,'issue');this.selectedIndex='0';" >
                <option value="0" selected="selected">Selecione uma resposta/questão pré definida</option>
                <?while(list($cannedId,$title)=db_fetch_row($canned)) { ?>
                <option value="<?=$cannedId?>" ><?=Format::htmlchars($title)?></option>
                <?}?>
              </select>&nbsp;&nbsp;&nbsp;<label><input type='checkbox' value='1' name=append checked="true" />Anexar</label>
            <?}?>
            <textarea name="issue" cols="55" rows="8" wrap="soft"><?=$info['issue']?></textarea></td>
    </tr>
    <?if($cfg->canUploadFiles()) {
        ?>
    <tr>
        <td>Anexo:</td>
        <td>
            <input type="file" name="attachment"><font class="error">&nbsp;<?=$errors['attachment']?></font>
        </td>
    </tr>
    <?}?>
    <tr>
        <td align="left" valign="top">Nota Interna:</td>
        <td>
            <i>Nota Interna Opcional.</i><font class="error"><b>&nbsp;<?=$errors['note']?></b></font><br/>
            <textarea name="note" cols="55" rows="5" wrap="soft"><?=$info['note']?></textarea></td>
    </tr>

    <tr>
        <td align="left" valign="top">Data de Vencimento:</td>
        <td>
            <i>A hora é baseada  no seu fuso horário (GM <?=$thisuser->getTZoffset()?>)</i>&nbsp;<font class="error">&nbsp;<?=$errors['time']?></font><br>
            <input id="duedate" name="duedate" value="<?=Format::htmlchars($info['duedate'])?>"
                onclick="event.cancelBubble=true;calendar(this);" autocomplete=OFF>
            <a href="#" onclick="event.cancelBubble=true;calendar(getObj('duedate')); return false;"><img src='images/cal.png'border=0 alt=""></a>
            &nbsp;&nbsp;
            <?php
             $min=$hr=null;
             if($info['time'])
                list($hr,$min)=explode(':',$info['time']);
                echo Misc::timeDropdown($hr,$min,'time');
            ?>
            &nbsp;<font class="error">&nbsp;<?=$errors['duedate']?></font>
        </td>
    </tr>
    <?
      $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' ORDER BY priority_urgency DESC';
      if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
      <tr>
        <td align="left">Prioridade:</td>
        <td>
            <select name="pri">
              <?
                $info['pri']=$info['pri']?$info['pri']:$cfg->getDefaultPriorityId();
                while($row=db_fetch_array($priorities)){ ?>
                    <option value="<?=$row['priority_id']?>" <?=$info['pri']==$row['priority_id']?'selected':''?> ><?=$row['priority_desc']?></option>
              <?}?>
            </select>
        </td>
       </tr>
    <? }?>
    <?php
    $services= db_query('SELECT topic_id,topic FROM '.TOPIC_TABLE.' WHERE isactive=1 ORDER BY topic');
    if($services && db_num_rows($services)){ ?>
    <tr>
        <td align="left" valign="top">Tópico de Ajuda:</td>
        <td>
            <select name="topicId">
                <option value="" selected >Selecionar Um</option>
                <?
                 while (list($topicId,$topic) = db_fetch_row($services)){
                    $selected = ($info['topicId']==$topicId)?'selected':''; ?>
                    <option value="<?=$topicId?>"<?=$selected?>><?=$topic?></option>
                <?
                 }?>
            </select>
            &nbsp;<font class="error">&nbsp;<?=$errors['topicId']?></font>
        </td>
    </tr>
    <?
    }?>
    <tr>
        <td>Atribuir Para:</td>
        <td>
            <select id="staffId" name="staffId">
                <option value="0" selected="selected">-Atribuição-</option>
                <?
                    //TODO: make sure the user's group is also active....DO a join.
                    $sql=' SELECT staff_id,CONCAT_WS(", ",lastname,firstname) as name FROM '.STAFF_TABLE.' WHERE isactive=1 AND onvacation=0 ';
                    $depts= db_query($sql.' ORDER BY lastname,firstname ');
                    while (list($staffId,$staffName) = db_fetch_row($depts)){
                        $selected = ($info['staffId']==$staffId)?'selected':''; ?>
                        <option value="<?=$staffId?>"<?=$selected?>><?=$staffName?></option>
                    <?
                    }?>
            </select><font class='error'>&nbsp;<?=$errors['staffId']?></font>
                &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="alertstaff" <?=(!$errors || $info['alertstaff'])? 'checked': ''?>>Send alert to assigned staff.
        </td>
    </tr>
    <tr>
        <td>Assinatura:</td>
        <td> <?php
            $appendStaffSig=$thisuser->appendMySignature();
            $info['signature']=!$info['signature']?'none':$info['signature']; //change 'none' to 'mine' to default to staff signature.
            ?>
            <div style="margin-top: 2px;">
                <label><input type="radio" name="signature" value="none" checked > Nenhuma</label>
                <?if($appendStaffSig) {?>
                    <label> <input type="radio" name="signature" value="mine" <?=$info['signature']=='mine'?'checked':''?> > Minha assinatura</label>
                 <?}?>
                 <label><input type="radio" name="signature" value="dept" <?=$info['signature']=='dept'?'checked':''?> > Assinatura do Departamento (se for o caso)</label>
            </div>
        </td>
    </tr>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr>
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Submeter Ticket">
            <input class="button" type="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="tickets.php"'>    
        </td>
    </tr>
  </form>
</table>
<script type="text/javascript">
    
    var options = {
        script:"ajax.php?api=tickets&f=searchbyemail&limit=10&",
        varname:"input",
        json: true,
        shownoresults:false,
        maxresults:10,
        callback: function (obj) { document.getElementById('email').value = obj.id; document.getElementById('name').value = obj.info; return false;}
    };
    var autosug = new bsn.AutoSuggest('email', options);
</script>
