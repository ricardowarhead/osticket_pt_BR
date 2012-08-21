<?php
/*************************************************************************
    tickets.php
    
    Handles all tickets related actions.
 
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.banlist.php');


$page='';
$ticket=null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if(!$errors && ($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['ticket_id']) && is_numeric($id)) {
    $deptID=0;
    $ticket= new Ticket($id);
    if(!$ticket or !$ticket->getDeptId())
        $errors['err']='Ticket desconhecido, ID#'.$id; //Sucker...invalid id
    elseif(!$thisuser->isAdmin()  && (!$thisuser->canAccessDept($ticket->getDeptId()) && $thisuser->getId()!=$ticket->getStaffId()))
        $errors['err']='Acesso negado. Contate o administrador se você acredita que isso é um erro.';

    if(!$errors && $ticket->getId()==$id)
        $page='viewticket.inc.php'; //Default - view

    if(!$errors && $_REQUEST['a']=='edit') { //If it's an edit  check permission.
        if($thisuser->canEditTickets() || ($thisuser->isManager() && $ticket->getDeptId()==$thisuser->getDeptId()))
            $page='editticket.inc.php';
        else
            $errors['err']='Acesso negado. Você não tem permissão para editar este ticket. Contate o administrador se você acredita que isso é um erro.';
    }

}elseif($_REQUEST['a']=='open') {
    //TODO: Check perm here..
    $page='newticket.inc.php';
}
//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if($ticket && $ticket->getId()) {
        //More tea please.
        $errors=array();
        $lock=$ticket->getLock(); //Ticket lock if any
        $statusKeys=array('open'=>'Open','Reopen'=>'Open','Close'=>'Closed');
        switch(strtolower($_POST['a'])):
        case 'reply':
            $fields=array();
            $fields['msg_id']       = array('type'=>'int',  'required'=>1, 'error'=>'Missing message ID');
            $fields['response']     = array('type'=>'text', 'required'=>1, 'error'=>'Response message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            //Use locks to avoid double replies
            if($lock && $lock->getStaffId()!=$thisuser->getId())
                $errors['err']='Ação negada. O ticket está bloqueado por outra pessoa!';

            //Check attachments restrictions.
            if($_FILES['attachment'] && $_FILES['attachment']['size']) {
                if(!$_FILES['attachment']['name'] || !$_FILES['attachment']['tmp_name'])
                    $errors['attachment']='Anexo inválido';
                elseif(!$cfg->canUploadFiles()) //TODO: saved vs emailed attachments...admin config??
                    $errors['attachment']='Diretório de upload inválido. Contate o administrador.';
                elseif(!$cfg->canUploadFileType($_FILES['attachment']['name']))
                    $errors['attachment']='Tipo de arquivo inválido';
            }

            //Make sure the email is not banned
            if(!$errors && BanList::isbanned($ticket->getEmail()))
                $errors['err']='O e-mail está na banlist. Deve ser removido para responder.';

            //If no error...do the do.
            if(!$errors && ($respId=$ticket->postResponse($_POST['msg_id'],$_POST['response'],$_POST['signature'],$_FILES['attachment']))){
                $msg='Resposta enviada com sucesso!';
                //Set status if any.
                $wasOpen=$ticket->isOpen();
                if(isset($_POST['ticket_status']) && $_POST['ticket_status']) {
                   if($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()) {
                       $note=sprintf('%s %s o ticket em resposta',$thisuser->getName(),$ticket->isOpen()?'reopened':'closed');
                       $ticket->logActivity('Estado do ticket mudado para '.($ticket->isOpen()?'Open':'Closed'),$note);
                   }
                }
                //Finally upload attachment if any
                if($_FILES['attachment'] && $_FILES['attachment']['size']){
                    $ticket->uploadAttachment($_FILES['attachment'],$respId,'R');
                }
                $ticket->reload();
                //Mark the ticket answered if OPEN.
                if($ticket->isopen()){
                    $ticket->markAnswered();
                }elseif($wasOpen) { //Closed on response???
                    $page=$ticket=null; //Going back to main listing.
                }
            }elseif(!$errors['err']){
                $errors['err']='Não foi possível enviar a resposta.';
            }
            break;
        case 'transfer':
            $fields=array();
            $fields['dept_id']      = array('type'=>'int',  'required'=>1, 'error'=>'Select Department');
            $fields['message']      = array('type'=>'text',  'required'=>1, 'error'=>'Note/Message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }

            if(!$errors && ($_POST['dept_id']==$ticket->getDeptId()))
                $errors['dept_id']='Ticket já no Departamento.';
       
            if(!$errors && !$thisuser->canTransferTickets())
                $errors['err']='Ação negada. Você não tem permissão para transferir tickets.';
            
            if(!$errors && $ticket->transfer($_POST['dept_id'])){
                 $olddept=$ticket->getDeptName();
                 $ticket->reload(); //dept manager changed!
                //Send out alerts?? - for now yes....part of internal note!
                $title='Departamento transferir do '.$olddept.' to '.$ticket->getDeptName();
                $ticket->postNote($title,$_POST['message']);
                $msg='Ticket transferido com sucesso para '.$ticket->getDeptName().' Dept.';
                if(!$thisuser->canAccessDept($_POST['dept_id']) && $ticket->getStaffId()!=$thisuser->getId()) { //Check access.
                    //Staff doesn't have access to the new department.
                    $page='tickets.inc.php';
                    $ticket=null;
                }
            }elseif(!$errors['err']){
                $errors['err']='Não foi possível completar a transferência.';
            }
            break;
        case 'assign':
            $fields=array();
            $fields['staffId']          = array('type'=>'int',  'required'=>1, 'error'=>'Select assignee');
            $fields['assign_message']   = array('type'=>'text',  'required'=>1, 'error'=>'Message required');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            if(!$errors && $ticket->isAssigned()){
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket já atribuído ao atendente.';
            }
            //if already assigned.
            if(!$errors && $ticket->isAssigned()) { //Re assigning.
                //Already assigned to the user?
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket já atribuído ao atendente.';
                //Admin, Dept manager (any) or current assigneee ONLY can reassign
                if(!$thisuser->isadmin()  && !$thisuser->isManager() && $thisuser->getId()!=$ticket->getStaffId())
                    $errors['err']='Ticket já atribuído. Você não tem permissão para re-atribuir tickets atribuídos.';
            }
            if(!$errors && $ticket->assignStaff($_POST['staffId'],$_POST['assign_message'])){
                $staff=$ticket->getStaff();
                $msg='Ticket atribuído a '.($staff?$staff->getName():'staff');
                //Remove all the logs and go back to index page.
                TicketLock::removeStaffLocks($thisuser->getId(),$ticket->getId());
                $page='tickets.inc.php';
                $ticket=null;
            }elseif(!$errors['err']) {
                $errors['err']='Não foi possível atribuir o ticket.';
            }
            break; 
        case 'postnote':
            $fields=array();
            $fields['title']    = array('type'=>'string',   'required'=>1, 'error'=>'Título necessário');
            $fields['note']     = array('type'=>'string',   'required'=>1, 'error'=>'Mensagem de nota necessária');
            $params = new Validator($fields);
            if(!$params->validate($_POST))
                $errors=array_merge($errors,$params->errors());

            if(!$errors && $ticket->postNote($_POST['title'],$_POST['note'])){
                $msg='Nota interna enviada';
                if(isset($_POST['ticket_status']) && $_POST['ticket_status']){
                    if($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()){
                        $msg.=' e estado configurado para '.($ticket->isClosed()?'closed':'open');
                        if($ticket->isClosed())
                            $page=$ticket=null; //Going back to main listing.
                    }
                }
            }elseif(!$errors['err']) {
                $errors['err']='Erros ocorreram. Não foi possível enviar a nota.';
            }
            break;
        case 'update':
            $page='editticket.inc.php';
            if(!$ticket || !$thisuser->canEditTickets())
                $errors['err']='Permissão negada. Você não tem permissão para editar tickets.';
            elseif($ticket->update($_POST,$errors)){
                $msg='Ticket atualizado com sucesso';
                $page='viewticket.inc.php';
            }elseif(!$errors['err']) {
                $errors['err']='Erros ocorreram! Tente novamente.';
            }
            break;
        case 'process':
            $isdeptmanager=($ticket->getDeptId()==$thisuser->getDeptId())?true:false;
            switch(strtolower($_POST['do'])):
                case 'change_priority':
                    if(!$thisuser->canManageTickets() && !$thisuser->isManager()){
                        $errors['err']='Permissão negada. Você não tem permissão para trocar a prioridade de ticket\'s';
                    }elseif(!$_POST['ticket_priority'] or !is_numeric($_POST['ticket_priority'])){
                        $errors['err']='Você deve selecionar uma prioridade';
                    }
                    if(!$errors){
                        if($ticket->setPriority($_POST['ticket_priority'])){
                            $msg='Prioridade mudada com sucesso';
                            $ticket->reload();
                            $note='Prioridade do ticket configurada para "'.$ticket->getPriority().'" by '.$thisuser->getName();
                            $ticket->logActivity('Prioridade trocada',$note);
                        }else{
                            $errors['err']='Problemas na mudança de prioridade. Tente novamente';
                        }
                    }
                    break;
                case 'close':
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Permissão negada. Você não tem permissão para fechar tickets.';
                    }else{
                        if($ticket->close()){
                            $msg='Ticket #'.$ticket->getExtId().' estado configurado como FECHADO';
                            $note='Ticket fechado sem resposta por '.$thisuser->getName();
                            $ticket->logActivity('Ticket Fechado',$note);
                            $page=$ticket=null; //Going back to main listing.
                        }else{
                            $errors['err']='Problemas no fechamento do ticket. Tente novamente';
                        }
                    }
                    break;
                case 'reopen':
                    //if they can close...then assume they can reopen.
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Permissão negada. Você não tem permissaõ para reabrir tickets.';
                    }else{
                        if($ticket->reopen()){
                            $msg='Estado do ticket configurado para ABERTO';
                            $note='Ticket reaberto (sem comentários)';
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' e o estado configurado para '.$ticket->getPriority();
                            }
                            $note.=' by '.$thisuser->getName();
                            $ticket->logActivity('Ticket reaberto',$note);
                        }else{
                            $errors['err']='Problemas na reabertura do ticket. Tente novamente';
                        }
                    }
                    break;
                case 'release':
                    if(!($staff=$ticket->getStaff()))
                        $errors['err']='Ticket não está atribuído!';
                    elseif($ticket->release()) {
                        $msg='Ticket liberado (sem atribuição) de '.$staff->getName().' by '.$thisuser->getName();;
                        $ticket->logActivity('Ticket sem atribuição',$msg);
                    }else
                        $errors['err']='Problemas na liberação do ticket. Tente novamente.';
                    break;
                case 'overdue':
                    //Mark the ticket as overdue
                    if(!$thisuser->isadmin() && !$thisuser->isManager()){
                        $errors['err']='Permissão negada. Você não tem permissão para sinalizar tickets vencidos.';
                    }else{
                        if($ticket->markOverdue()){
                            $msg='Ticket sinalizado como vencido';
                            $note=$msg;
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' e estado configurado para '.$ticket->getPriority();
                            }
                            $note.=' by '.$thisuser->getName();
                            $ticket->logActivity('Ticket marcado como vencido',$note);
                        }else{
                            $errors['err']='Problemas na marcação do ticket vencido. Tente novamente.';
                        }
                    }
                    break;
                case 'banemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Permissão negada. Você não tem permissão para banir/proibir e-mails';
                    }elseif(Banlist::add($ticket->getEmail(),$thisuser->getName())){
                        $msg='Email ('.$ticket->getEmail().') adicionado na banlist';
                        if($ticket->isOpen() && $ticket->close()) {
                            $msg.=' & estado do ticket configurado para fechado';
                            $ticket->logActivity('Ticket Fechado',$msg);
                            $page=$ticket=null; //Going back to main listing.
                        }
                    }else{
                        $errors['err']='Não foi possível adicionar o e-mail na banlist';
                    }
                    break;
                case 'unbanemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Permissão negada. Você não tem permissão para remover e-mails da banlist.';
                    }elseif(Banlist::remove($ticket->getEmail())){
                        $msg='E-mail removido da banlist';
                    }else{
                        $errors['err']='Não foi possível remover o e-mail da banlist. Tente novamente.';
                    }
                    break;
                case 'delete': // Dude what are you trying to hide? bad customer support??
                    if(!$thisuser->isadmin() && !$thisuser->canDeleteTickets()){
                        $errors['err']='Perm. Negado. Você não tem permissão para EXCLUIR tickets!!';
                    }else{
                        if($ticket->delete()){
                            $page='tickets.inc.php'; //ticket is gone...go back to the listing.
                            $msg='Ticket Deleted Forever';
                            $ticket=null; //clear the object.
                        }else{
                            $errors['err']='Problemas na exclusão do ticket. Tente novamente';
                        }
                    }
                    break;
                default:
                    $errors['err']='Você deve selecionar a ação a ser executada';
            endswitch;
            break;
        default:
            $errors['err']='Ação desconhecida';
        endswitch;
        if($ticket && is_object($ticket))
            $ticket->reload();//Reload ticket info following post processing
    }elseif($_POST['a']) {
        switch($_POST['a']) {
            case 'mass_process':
                if(!$thisuser->canManageTickets())
                    $errors['err']='Você não tem permissão para gerenciamento em massa de tickets. Contate o administrador para esse acesso';    
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='Tickets não selecionados. Você deve selecinar pelo menos um ticket.';
                elseif(($_POST['reopen'] || $_POST['close']) && !$thisuser->canCloseTickets())
                    $errors['err']='Você não tem permissão para fechar/reabrir tickets.';
                elseif($_POST['delete'] && !$thisuser->canDeleteTickets())
                    $errors['err']='Você não tem permissão para excluir tickets.';
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='Você deve selecionar pelo menos um ticket.';
        
                if(!$errors) {
                    $count=count($_POST['tids']);
                    if(isset($_POST['reopen'])){
                        $i=0;
                        $note='Ticket reaberto por '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->reopen()) {
                                $i++;
                                $t->logActivity('Ticket Reaberto',$note,false,'Sistema');
                            }
                        }
                        $msg="$i de $count dos tickets selecionados foram reabertos";
                    }elseif(isset($_POST['close'])){
                        $i=0;
                        $note='Ticket fechado sem resposta por '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->close()){ 
                                $i++;
                                $t->logActivity('Ticket Fechado',$note,false,'Sistema');
                            }
                        }
                        $msg="$i de $count dos tickets selecionados foram fechados";
                    }elseif(isset($_POST['overdue'])){
                        $i=0;
                        $note='Ticket sinalizado como vencido por '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && !$t->isoverdue())
                                if($t->markOverdue()) { 
                                    $i++;
                                    $t->logActivity('Ticket marcado como vencido',$note,false,'Sistema');
                                }
                        }
                        $msg="$i de $count dos tickets selecionados foram marcados como vencido";
                    }elseif(isset($_POST['delete'])){
                        $i=0;
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->delete()) $i++;
                        }
                        $msg="$i de $count dos tickets selecionados foram marcados como excluído";
                    }
                }
                break;
            case 'open':
                $ticket=null;
                //TODO: check if the user is allowed to create a ticet.
                if(($ticket=Ticket::create_by_staff($_POST,$errors))) {
                    $ticket->reload();
                    $msg='Ticket criado com sucesso';
                    if($thisuser->canAccessDept($ticket->getDeptId()) || $ticket->getStaffId()==$thisuser->getId()) {
                        //View the sucker
                        $page='viewticket.inc.php';
                    }else {
                        //Staff doesn't have access to the newly created ticket's department.
                        $page='tickets.inc.php';
                        $ticket=null;
                    }
                }elseif(!$errors['err']) {
                    $errors['err']='Não foi possível criar o ticket. Corrija o erro e tente novamente.';
                }
                break;
        }
    }
    $crap='';
endif;
//Navigation 
$submenu=array();
/*quick stats...*/
$sql='SELECT count(open.ticket_id) as open, count(answered.ticket_id) as answered '.
     ',count(overdue.ticket_id) as overdue, count(assigned.ticket_id) as assigned '.
     ' FROM '.TICKET_TABLE.' ticket '.
     'LEFT JOIN '.TICKET_TABLE.' open ON open.ticket_id=ticket.ticket_id AND open.status=\'open\' AND open.isanswered=0 '.
     'LEFT JOIN '.TICKET_TABLE.' answered ON answered.ticket_id=ticket.ticket_id AND answered.status=\'open\' AND answered.isanswered=1 '.
     'LEFT JOIN '.TICKET_TABLE.' overdue ON overdue.ticket_id=ticket.ticket_id AND overdue.status=\'open\' AND overdue.isoverdue=1 '.
     'LEFT JOIN '.TICKET_TABLE.' assigned ON assigned.ticket_id=ticket.ticket_id AND assigned.staff_id='.db_input($thisuser->getId());
if(!$thisuser->isAdmin()){
    $sql.=' WHERE ticket.dept_id IN('.implode(',',$thisuser->getDepts()).') OR ticket.staff_id='.db_input($thisuser->getId());
}
//echo $sql;

$stats=db_fetch_array(db_query($sql));
//print_r($stats);
$nav->setTabActive('tickets');

if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>'Abrir ('.($stats['open']+$stats['answered']).')'
                            ,'title'=>'Open Tickets', 'href'=>'tickets.php', 'iconclass'=>'Ticket'));
}else{
    if($stats['open'])
        $nav->addSubMenu(array('desc'=>'Abrir ('.$stats['open'].')','title'=>'Open Tickets', 'href'=>'tickets.php', 'iconclass'=>'Ticket'));
    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Respondido ('.$stats['answered'].')',
                           'title'=>'Answered Tickets', 'href'=>'tickets.php?status=answered', 'iconclass'=>'answeredTickets')); 
    }
}

if($stats['assigned']) {
    if(!$sysnotice && $stats['assigned']>10)
        $sysnotice=$stats['assigned'].' atribuído para você!';

    $nav->addSubMenu(array('desc'=>'Meus Tickets ('.$stats['assigned'].')','title'=>'Assigned Tickets',
                    'href'=>'tickets.php?status=assigned','iconclass'=>'assignedTickets'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'Vencido ('.$stats['overdue'].')','title'=>'Stale Tickets',
                    'href'=>'tickets.php?status=overdue','iconclass'=>'overdueTickets'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' tickets vencidos!';
}

$nav->addSubMenu(array('desc'=>'Tickets Fechados','title'=>'Tickets Fechados', 'href'=>'tickets.php?status=closed', 'iconclass'=>'closedTickets'));


if($thisuser->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'Novo Ticket','href'=>'tickets.php?a=open','iconclass'=>'newTicket'));    
}

//Render the page...
$inc=$page?$page:'tickets.inc.php';

//If we're on tickets page...set refresh rate if the user has it configured. No refresh on search and POST to avoid repost.
if(!$_POST && $_REQUEST['a']!='search' && !strcmp($inc,'tickets.inc.php') && ($min=$thisuser->getRefreshRate())){ 
    define('AUTO_REFRESH',1);
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
