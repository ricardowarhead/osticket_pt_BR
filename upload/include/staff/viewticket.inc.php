<?php
//Note that ticket is initiated in tickets.php.
if(!defined('OSTSCPINC') || !@$thisuser->isStaff() || !is_object($ticket) ) die('Diretório Inválido');
if(!$ticket->getId() or (!$thisuser->canAccessDept($ticket->getDeptId()) and $thisuser->getId()!=$ticket->getStaffId())) die('Acesso Negado');

$info=($_POST && $errors)?Format::input($_POST):array(); //Re-use the post info on error...savekeyboards.org

//Auto-lock the ticket if locking is enabled..if locked already simply renew it.
if($cfg->getLockTime() && !$ticket->acquireLock())
    $warn.='Não foi possível obter um bloqueio no ticket';

//We are ready baby...lets roll. Akon rocks! 
$dept  = $ticket->getDept();  //Dept
$staff = $ticket->getStaff(); //Assiged staff.
$lock  = $ticket->getLock();  //Ticket lock obj
$id=$ticket->getId(); //Ticket ID.

if($staff)
    $warn.='&nbsp;&nbsp;<span class="Icon assignedTicket">O ticket está atribuído para '.$staff->getName().'</span>';
if(!$errors['err'] && ($lock && $lock->getStaffId()!=$thisuser->getId()))
    $errors['err']='Este ticket está atualmente bloqueado por outro membro atendente!';
if(!$errors['err'] && ($emailBanned=BanList::isbanned($ticket->getEmail())))
    $errors['err']='O e-mail está na banlist! Deve ser removido antes de qualquer resposta ';    
if($ticket->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">Marcado como vencido!</span>';
    
?>
<table width="100%" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td class="msg" width=50%>
            Ticket #<?=$ticket->getExtId()?>&nbsp;<a href="tickets.php?id=<?=$id?>" title="Reload"><span class="Icon refresh">&nbsp;</span></a></td>

        <td class="msg" width=50%>
            <? if($thisuser->canEditTickets() || ($thisuser->isManager() && $dept->getId()==$thisuser->getDeptId())) { ?>
             <a href="tickets.php?id=<?=$id?>&a=edit" title="Editar Ticket" class="Icon editTicket">Editar Ticket</a>
            <?}?>
        </td>
    </tr>
    <tr>
     <td width=50%>	
		<table align="center" class="ticketinfo" cellspacing="1" cellpadding="3" width="100%" border=0>
			<tr>
				<th>Estado/Situação:</th>
				<td><?=$ticket->getStatus()?></td>
			</tr>
			<tr>
        		<th>Prioridade:</th>
        		<td><?=$ticket->getPriority()?></td>
   	 		</tr>
            <tr>
                <th>Departamento:</th>
                <td><?=Format::htmlchars($ticket->getDeptName())?></td>
            </tr>
			<tr>
                <th>Data de criação:</th>
                <td><?=Format::db_datetime($ticket->getCreateDate())?></td>
            </tr>
		</table>
     </td>
     <td width=50% valign="top">
        <table align="center" class="ticketinfo" cellspacing="1" cellpadding="3" width="100%" border=0>
            <tr>
                <th>Nome:</th>
                <td><?=Format::htmlchars($ticket->getName())?></td>
            </tr>
            <tr>
                <th>E-mail:</th>
                <td><?php 
                    echo $ticket->getEmail();
                    if(($related=$ticket->getRelatedTicketsCount())) {
                        echo sprintf('&nbsp;&nbsp;<a href="tickets.php?a=search&query=%s" title="Tickets Relacionados">(<b>%d</b>)</a>',
                                    urlencode($ticket->getEmail()),$related);
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Telefone:</th>
                <td><?=Format::phone($ticket->getPhoneNumber())?></td>
            </tr>
            <tr>
                <th>Origem:</th>
                <td><?=$ticket->getSource()?></td>
            </tr>
        </table>
     </td>
    </tr>
    <tr><td colspan=2 class="msg">Assunto: <?=Format::htmlchars($ticket->getSubject())?></td></tr>
    <tr>
     <td valign="top" width=50%>
        <table align="center" class="ticketinfo" cellspacing="1" cellpadding="3" width="100%" border=0>
            <tr>
                <th>Função Pessoal:</th>
                <td><?=$staff?Format::htmlchars($staff->getName()):'- não atribuído -'?></td>
            </tr>
            <tr>
                <th nowrap>Última Resposta:</th>
                <td><?=Format::db_datetime($ticket->getLastResponseDate())?></td>
            </tr>
            <?php
            if($ticket->isOpen()){ ?>
            <tr>
                <th>Vencimento:</th>
                <td><?=Format::db_datetime($ticket->getDueDate())?></td>
            </tr>
            <?php
            }else { ?>
            <tr>
                <th>Data de fechamento:</th>
                <td><?=Format::db_datetime($ticket->getCloseDate())?></td>
            </tr>
            <?php
            }
            ?>
        </table>
     </td>
     <td width=50% valign="top">
        <table align="center" class="ticketinfo" cellspacing="1" cellpadding="3" width="100%" border=0>
            <tr><th>Tópico da ajuda:</th>
                <td><?
                    $ht=$ticket->getHelpTopic();
                    echo Format::htmlchars($ht?$ht:'N/A');
                    ?>
                </td>
            </tr>
            <tr>
                <th>Endereço IP:</th>
                <td><?=$ticket->getIP()?></td>
            </tr>
            <tr><th nowrap>Última Mensagem:</th>
                <td><?=Format::db_datetime($ticket->getLastMessageDate())?></td>
            </tr>
        </table>
     </td>
    </tr>
</table>
<div>
    <?if($errors['err'] && $_POST['a']=='process') {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg && $_POST['a']=='process' || $_POST['a']=='update' ) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<?
//Ticket adminstrative options...admin,managers and staff with manage perm allowed.
if($thisuser->canManageTickets() || $thisuser->isManager()){ ?> 
<table cellpadding="0" cellspacing="2" border="0" width="100%" class="ticketoptions">
    <tr><td>
        <form name=action action='tickets.php?id=<?=$id?>' method=post class="inline" >
            <input type='hidden' name='ticket_id' value="<?=$id?>"/>
             <input type='hidden' name='a' value="process"/>
            <span for="do"> &nbsp;<b>Ação:</b></span>
            <select id="do" name="do" 
              onChange="this.form.ticket_priority.disabled=strcmp(this.options[this.selectedIndex].value,'change_priority','reopen','overdue')?false:true;">
                <option value="">Selecione a Ação</option>
                <option value="change_priority" <?=$info['do']=='change_priority'?'selected':''?> >Escolha a Prioridade</option>
                <?if(!$ticket->isoverdue()){ ?>
                <option value="overdue" <?=$info['do']=='overdue'?'selected':''?> >Marcar Atraso</option>
                <?}?>
                <?if($ticket->isAssigned()){ ?>
                <option value="release" <?=$info['do']=='release'?'selected':''?> >Relocar (não atribuir)</option>
                <?}?>
                
                <?if($thisuser->canCloseTickets()){
                    //if you can close a ticket...reopening it is given.
                    if($ticket->isOpen()){?>
                     <option value="close" <?=$info['do']=='close'?'selected':''?> >Fechar Ticket</option>
                    <?}else{?>
                        <option value="reopen" <?=$info['do']=='reopen'?'selected':''?> >Reabrir Ticket</option>
                    <?}
                }?>
                <?php
                 if($thisuser->canManageBanList()) {
                    if(!$emailBanned) {?>    
                        <option value="banemail" >Proibir E-mail <?=$ticket->isOpen()?'&amp; Close':''?></option>
                    <?}else{?>
                        <option value="unbanemail">Liberar E-mail</option>
                    <?}
                 }?>
                
                <?if($thisuser->canDeleteTickets()){ //oooh...fear the deleters! ?>
                <option value="delete" >Excluir Ticket</option>
                <?}?>
            </select>
            <span for="ticket_priority">Prioridade:</span>
            <select id="ticket_priority" name="ticket_priority" <?=!$info['do']?'disabled':''?> >
                <option value="0" selected="selected">-Inalterado-</option>
                <?
                $priorityId=$ticket->getPriorityId();
                $resp=db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                while($row=db_fetch_array($resp)){ ?>
                    <option value="<?=$row['priority_id']?>" <?=$priorityId==$row['priority_id']?'disabled':''?> ><?=$row['priority_desc']?></option>
                <?}?>
            </select>
                &nbsp;&nbsp;
            <input class="button" type="submit" value="Avançar">
        </form>
    </tr></td>
</table>
<?}?>
<br>
<?
//Internal Notes

$sql ='SELECT note_id,title,note,source,created FROM '.TICKET_NOTE_TABLE.' WHERE ticket_id='.db_input($id).' ORDER BY created DESC';
if(($resp=db_query($sql)) && ($notes=db_num_rows($resp))){
    $display=($notes>5)?'none':'block'; //Collapse internal notes if more than 5.
?>
<div align="left">
    <a class="Icon note" href="#" onClick="toggleLayer('ticketnotes'); return false;">Notas Internas (<?=$notes?>)</a><br><br>
    <div id='ticketnotes' style="display:<?=$display?>;text-align:center;"> 
        <?
        while($row=db_fetch_array($resp)) {?>
        <table align="center" class="note" cellspacing="0" cellpadding="1" width="100%" border=0>
            <tr><th><?=Format::db_daydatetime($row['created'])?>&nbsp;-&nbsp; postado por <?=$row['source']?></th></tr>
            <? if($row['title']) {?>
            <tr class="header"><td><?=Format::display($row['title'])?></td></tr>
            <?} ?>
            <tr><td><?=Format::display($row['note'])?></td></tr>
        </table>
     <?} ?>
   </div>
</div>
<?} ?>
<div align="left">
    <a class="Icon thread" href="#" onClick="toggleLayer('ticketthread'); return false;">Tópico do Ticket</a>
    <div id="ticketthread">
	<?
	    //get messages
        $sql='SELECT msg.msg_id,msg.created,msg.message,count(attach_id) as attachments  FROM '.TICKET_MESSAGE_TABLE.' msg '.
            ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE." attach ON  msg.ticket_id=attach.ticket_id AND msg.msg_id=attach.ref_id AND ref_type='M' ".
            ' WHERE  msg.ticket_id='.db_input($id).
            ' GROUP BY msg.msg_id ORDER BY created'; 
	    $msgres =db_query($sql);
	    while ($msg_row = db_fetch_array($msgres)) {
		    ?>
		    <table align="center" class="message" cellspacing="0" cellpadding="1" width="100%" border=0>
		        <tr><th><?=Format::db_daydatetime($msg_row['created'])?></th></tr>
                <?if($msg_row['attachments']>0){ ?>
                <tr class="header"><td><?=$ticket->getAttachmentStr($msg_row['msg_id'],'M')?></td></tr> 
                <?}?>
                <tr><td><?=Format::display($msg_row['message'])?>&nbsp;</td></tr>
		    </table>
            <?
            //get answers for messages
            $sql='SELECT resp.*,count(attach_id) as attachments FROM '.TICKET_RESPONSE_TABLE.' resp '.
                ' LEFT JOIN '.TICKET_ATTACHMENT_TABLE." attach ON  resp.ticket_id=attach.ticket_id AND resp.response_id=attach.ref_id AND ref_type='R' ".
                ' WHERE msg_id='.db_input($msg_row['msg_id']).' AND resp.ticket_id='.db_input($id).
                ' GROUP BY resp.response_id ORDER BY created';
		    $resp =db_query($sql);
		    while ($resp_row = db_fetch_array($resp)) {
                $respID=$resp_row['response_id'];
                ?>
    		    <table align="center" class="response" cellspacing="0" cellpadding="1" width="100%" border=0>
    		        <tr><th><?=Format::db_daydatetime($resp_row['created'])?>&nbsp;-&nbsp;<?=$resp_row['staff_name']?></th></tr>
                    <?if($resp_row['attachments']>0){ ?>
                    <tr class="header">
                        <td><?=$ticket->getAttachmentStr($respID,'R')?></td></tr>
                    <?}?>
			        <tr><td> <?=Format::display($resp_row['response'])?></td></tr>
		        </table>
	        <?}
            $msgid =$msg_row['msg_id'];
	    }?>
    </div>
</div>
<table align="center" cellspacing="0" cellpadding="3" width="90%" border=0>
  <?if($_POST['a']!='process') {?>
  <tr> <td align="center">
     <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}?> 
  </td></tr>
  <?}?>
  <tr> <td align="center">
        <div class="tabber">
            <div id="reply" class="tabbertab" align="left">
                <h2>Post Reply</h2>
                <p>
                    <form action="tickets.php?id=<?=$id?>#reply" name="reply" id="replyform" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" value="<?=$id?>">
                        <input type="hidden" name="msg_id" value="<?=$msgid?>">
                        <input type="hidden" name="a" value="responder">
                        <div><font class="error">&nbsp;<?=$errors['response']?></font></div>
                        <div>
                           <?
                             $sql='SELECT premade_id,title FROM '.KB_PREMADE_TABLE.' WHERE isenabled=1 '.
                                ' AND (dept_id=0 OR dept_id='.db_input($ticket->getDeptId()).')';
                            $canned=db_query($sql);
                            if($canned && db_num_rows($canned)) {
                             ?>
                               Modelo de Resposta:&nbsp;
                               <select id="canned" name="canned"
                                onChange="getCannedResponse(this.options[this.selectedIndex].value,this.form,'response');this.selectedIndex='0';" >
                                <option value="0" selected="selected">Selecione uma resposta pronta</option>
                                <?while(list($cannedId,$title)=db_fetch_row($canned)) { ?>
                                 <option value="<?=$cannedId?>" ><?=Format::htmlchars($title)?></option>
                                <?}?>
                               </select>&nbsp;&nbsp;&nbsp;<label><input type='checkbox' value='1' name=append checked="true" />Anexar</label>
                            <?}?>
                            <textarea name="response" id="response" cols="90" rows="9" wrap="soft" style="width:90%"><?=$info['response']?></textarea>
                        </div>
                        <?php if($cfg->canUploadFiles()){ //TODO: may be allow anyways and simply email out attachment?? ?>
                        <div style="margin-top: 3px;">
                            <label for="attachment" >Anexar Arquivo:</label>
                            <input type="file" name="attachment" id="attachment" size=30px value="<?=$info['attachment']?>" /> 
                                <font class="error">&nbsp;<?=$errors['attachment']?></font>
                        </div>
                        <?php }?>
                        <?
                         $appendStaffSig=$thisuser->appendMySignature();
                         $appendDeptSig=$dept->canAppendSignature();
                         $info['signature']=!$info['signature']?'none':$info['signature']; //change 'none' to 'mine' to default to staff signature.
                         if($appendStaffSig || $appendDeptSig) { ?>
                          <div style="margin-top: 10px;">
                                <label for="signature" nowrap>Anexar Assinatura:</label>
                                <label><input type="radio" name="signature" value="none" checked > Nenhuma</label>
                                <?if($appendStaffSig) {?>
                               <label> <input type="radio" name="signature" value="mine" <?=$info['signature']=='mine'?'checked':''?> > Minha Assinatura</label>
                                <?}?>
                                <?if($appendDeptSig) {?>
                                <label><input type="radio" name="signature" value="dept" <?=$info['signature']=='dept'?'checked':''?> > Assinatura do Departamento</label>
                                <?}?>
                           </div>
                         <?}?>
                        <div style="margin-top: 3px;">
                            <b>Estado/Situação do Ticket:</b>
                            <?
                            $checked=isset($info['ticket_status'])?'checked':''; //Staff must explicitly check the box to change status..
                            if($ticket->isOpen()){?>
                            <label><input type="checkbox" name="ticket_status" id="l_ticket_status" value="Fechar" <?=$checked?> > Fechar Resposta</label>
                            <?}else{ ?>
                            <label><input type="checkbox" name="ticket_status" id="l_ticket_status" value="Reabrir" <?=$checked?> > Abrir Resposta</label>
                            <?}?>
                        </div>
                        <p>
                            <div  style="margin-left: 50px; margin-top: 30px; margin-bottom: 10px;border: 0px;">
                                <input class="button" type='submit' value='Enviar Resposta' />
                                <input class="button" type='reset' value='Redefinir' />
                                <input class="button" type='button' value='Cancelar' onClick="history.go(-1)" />
                            </div>
                        </p>
                    </form>                
                </p>
            </div>
            <div id="notes" class="tabbertab"  align="left">
                <h2>Enviar Nota Interna</h2>
                <p>
                    <form action="tickets.php?id=<?=$id?>#notes" name="notes" class="inline" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" value="<?=$id?>">
                        <input type="hidden" name="a" value="postnote">
                        <div>
                            <label for="title">Título da Nota:</label>
                            <input type="text" name="title" id="title" value="<?=$info['title']?>" size=30px />
                            </select><font class="error">*&nbsp;<?=$errors['title']?></font>
                        </div>
                        <div style="margin-top: 3px;">
                            <label for="note" valign="top">Digite o conteúdo da nota.
                                <font class="error">*&nbsp;<?=$errors['note']?></font></label><br/>
                            <textarea name="note" id="note" cols="80" rows="7" wrap="soft" style="width:90%"><?=$info['note']?></textarea>
                        </div>

                        <?
                         //When the ticket is assigned Allow assignee, admin or ANY dept manager to close it
                        if(!$ticket->isAssigned() || $thisuser->isadmin()  || $thisuser->isManager() || $thisuser->getId()==$ticket->getStaffId()) {
                         ?>
                        <div style="margin-top: 3px;">
                            <b>Status do Ticket:</b>
                            <?
                            $checked=($info && isset($info['ticket_status']))?'checked':''; //not selected by default.
                            if($ticket->isOpen()){?>
                            <label><input type="checkbox" name="ticket_status" id="ticket_status" value="Fechar" <?=$checked?> > Fechar Ticket</label>
                            <?}else{ ?>
                            <label><input type="checkbox" name="ticket_status" id="ticket_status" value="Reabrir" <?=$checked?> > Reabrir Ticket</label>
                            <?}?>
                        </div>
                        <?}?>
                        <p>
                            <div  align="left" style="margin-left: 50px;margin-top: 10px; margin-bottom: 10px;border: 0px;">
                                <input class="button" type='submit' value='Aplicar' />
                                <input class="button" type='reset' value='Redefinir' />
                                <input class="button" type='button' value='Cancelar' onClick="history.go(-1)" />
                            </div>
                        </p>
                    </form>
                </p>
            </div>
            <?
            if($thisuser->canTransferTickets()) { 
                ?>
            <div id="transfer" class="tabbertab"  align="left">
                <h2>Dept. Transfer</h2>
                <p>

                    <form action="tickets.php?id=<?=$id?>#transfer" name="notes" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" value="<?=$id?>">
                        <input type="hidden" name="a" value="transfer">
                        <div>
                            <span for="dept_id">Departamento:</span>
                            <select id="dept_id" name="dept_id">
                                <option value="" selected="selected">-Selecione Departamento Alvo.-</option>
                                <?
                                $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id!='.db_input($ticket->getDeptId()));
                                while (list($deptId,$deptName) = db_fetch_row($depts)){
                                    $selected = ($info['dept_id']==$deptId)?'selected':''; ?>
                                    <option value="<?=$deptId?>"<?=$selected?>><?=$deptName?> Departamento </option>
                                <?
                                }?>
                            </select><font class='error'>&nbsp;*<?=$errors['dept_id']?></font>
                        </div>
                        <div>
                            <span >Motivo para a transferência. &nbsp;(<i>Nota interna</i>)
                                <font class='error'>&nbsp;*<?=$errors['message']?></font></span>
                            <textarea name="message" id="message" cols="80" rows="7" wrap="soft" style="width:90%;"><?=$info['message']?></textarea>
                        </div>
                        <p>
                            <div  style="margin-left: 50px; margin-top: 5px; margin-bottom: 10px;border: 0px;" align="left">
                                <input class="button" type='submit' value='Transferir' />
                                <input class="button" type='reset' value='Redefinir' />
                                <input class="button" type='button' value='Cancelar' onClick="history.go(-1)" />
                            </div>
                        </p>
                    </form>
                </p>
            </div>
            <?}?>
            <?
             //When the ticket is assigned Allow assignee, admin or ANY dept manager to reassign the ticket.
            if(!$ticket->isAssigned() || $thisuser->isadmin()  || $thisuser->isManager() || $thisuser->getId()==$ticket->getStaffId()) {
                 ?>
            <div id="assign" class="tabbertab"  align="left">
                
                <h2><?=$staff?'Re Atribuir Ticket':'Atribuir para Atendente'?></h2>
                <p>
                    <form action="tickets.php?id=<?=$id?>#assign" name="notes" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ticket_id" value="<?=$id?>">
                        <input type="hidden" name="a" value="atribuir">
                        <div>
                            <span for="staffId">Membro:</span>
                            <select id="staffId" name="staffId">
                                <option value="0" selected="selected">-Selecione membro da equipe.-</option>
                                <?
                                //TODO: make sure the user's group is also active....DO a join.
                                $sql=' SELECT staff_id,CONCAT_WS(", ",lastname,firstname) as name FROM '.STAFF_TABLE.
                                     ' WHERE isactive=1 AND onvacation=0 ';
                                if($ticket->isAssigned()) 
                                    $sql.=' AND staff_id!='.db_input($ticket->getStaffId());
                                $depts= db_query($sql.' ORDER BY lastname,firstname ');
                                while (list($staffId,$staffName) = db_fetch_row($depts)){
                                    
                                    $selected = ($info['staffId']==$staffId)?'selected':''; ?>
                                    <option value="<?=$staffId?>"<?=$selected?>><?=$staffName?></option>
                                <?
                                }?>
                            </select><font class='error'>&nbsp;*<?=$errors['staffId']?></font>
                        </div>
                        <div>
                            <span >Comentário/mensagem para destinatário. &nbsp;(<i>Salvo como nota interna</i>)
                                <font class='error'>&nbsp;*<?=$errors['assign_message']?></font></span>
                            <textarea name="assign_message" id="assign_message" cols="80" rows="7" 
                                wrap="soft" style="width:90%;"><?=$info['assign_message']?></textarea>
                        </div>
                        <p>
                            <div  style="margin-left: 50px; margin-top: 5px; margin-bottom: 10px;border: 0px;" align="left">
                                <input class="button" type='submit' value='Atribuir' />
                                <input class="button" type='reset' value='Redefinir' />
                                <input class="button" type='button' value='Cancelar' onClick="history.go(-1)" />
                            </div>
                        </p>
                    </form>
                </p>
            </div>
            <?}?>
        </div>
    </td>
 </tr>
</table>
