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
        $errors['err']='ID do ticket desconhecido#'.$id; //Sucker...invalid id
    elseif(!$thisuser->isAdmin()  && (!$thisuser->canAccessDept($ticket->getDeptId()) && $thisuser->getId()!=$ticket->getStaffId()))
        $errors['err']='Acesso Negado. Entre em contato com o administrador, se você acredita que este é um erro do sistema';

    if(!$errors && $ticket->getId()==$id)
        $page='viewticket.inc.php'; //Default - view

    if(!$errors && $_REQUEST['a']=='edit') { //If it's an edit  check permission.
        if($thisuser->canEditTickets() || ($thisuser->isManager() && $ticket->getDeptId()==$thisuser->getDeptId()))
            $page='editticket.inc.php';
        else
            $errors['err']='Acesso Negado. Você não tem permissão para editar este ticket. Fale com o administrador, se você acredita que este é um erro do sistema';
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
            $fields['msg_id']       = array('type'=>'int',  'required'=>1, 'error'=>'Falta ID da mensagem');
            $fields['response']     = array('type'=>'text', 'required'=>1, 'error'=>'Mensagem de resposta necessária');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            //Use locks to avoid double replies
            if($lock && $lock->getStaffId()!=$thisuser->getId())
                $errors['err']='Ação inválida. Ticket está bloqueado por outra pessoa!';

            //Check attachments restrictions.
            if($_FILES['attachment'] && $_FILES['attachment']['size']) {
                if(!$_FILES['attachment']['name'] || !$_FILES['attachment']['tmp_name'])
                    $errors['attachment']='Anexo inválido';
                elseif(!$cfg->canUploadFiles()) //TODO: saved vs emailed attachments...admin config??
                    $errors['attachment']='Carregamento inválido. Contacte o administrador.';
                elseif(!$cfg->canUploadFileType($_FILES['attachment']['name']))
                    $errors['attachment']='Tipo de arquivo inválido';
            }

            //Make sure the email is not banned
            if(!$errors && BanList::isbanned($ticket->getEmail()))
                $errors['err']='E-mail está na lista de banidos. Deve ser removido para responder';

            //If no error...do the do.
            if(!$errors && ($respId=$ticket->postResponse($_POST['msg_id'],$_POST['response'],$_POST['signature'],$_FILES['attachment']))){
                $msg='Resposta Postada com Sucesso.';
                //Set status if any.
                $wasOpen=$ticket->isOpen();
                if(isset($_POST['ticket_status']) && $_POST['ticket_status']) {
                   if($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()) {
                       $note=sprintf('%s %s o ticket em resposta',$thisuser->getName(),$ticket->isOpen()?'reaberto':'fechado');
                       $ticket->logActivity('Estado alterado para ticket '.($ticket->isOpen()?'Aberto':'Fechado'),$note);
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
                $errors['err']='Incapaz de postar a resposta.';
            }
            break;
        case 'transfer':
            $fields=array();
            $fields['dept_id']      = array('type'=>'int',  'required'=>1, 'error'=>'Selecione Departamento');
            $fields['message']      = array('type'=>'text',  'required'=>1, 'error'=>'Nota/Mensagem necessária');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }

            if(!$errors && ($_POST['dept_id']==$ticket->getDeptId()))
                $errors['dept_id']='Ticket já está no departamento.';
       
            if(!$errors && !$thisuser->canTransferTickets())
                $errors['err']='Ação negada. Você não está autorizado a transferir ticket.';
            
            if(!$errors && $ticket->transfer($_POST['dept_id'])){
                 $olddept=$ticket->getDeptName();
                 $ticket->reload(); //dept manager changed!
                //Send out alerts?? - for now yes....part of internal note!
                $title='Departamento de transferência de '.$olddept.' para '.$ticket->getDeptName();
                $ticket->postNote($title,$_POST['message']);
                $msg='Ticket transferido com sucesso para '.$ticket->getDeptName().' Dept.';
                if(!$thisuser->canAccessDept($_POST['dept_id']) && $ticket->getStaffId()!=$thisuser->getId()) { //Check access.
                    //Staff doesn't have access to the new department.
                    $page='tickets.inc.php';
                    $ticket=null;
                }
            }elseif(!$errors['err']){
                $errors['err']='Incapaz de completar a transferência';
            }
            break;
        case 'assign':
            $fields=array();
            $fields['staffId']          = array('type'=>'int',  'required'=>1, 'error'=>'Selecione destinatário');
            $fields['assign_message']   = array('type'=>'text',  'required'=>1, 'error'=>'Mensagem exigida');
            $params = new Validator($fields);
            if(!$params->validate($_POST)){
                $errors=array_merge($errors,$params->errors());
            }
            if(!$errors && $ticket->isAssigned()){
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket já atribuído à equipe.';
            }
            //if already assigned.
            if(!$errors && $ticket->isAssigned()) { //Re assigning.
                //Already assigned to the user?
                if($_POST['staffId']==$ticket->getStaffId())
                    $errors['staffId']='Ticket já atribuído ao atendente.';
                //Admin, Dept manager (any) or current assigneee ONLY can reassign
                if(!$thisuser->isadmin()  && !$thisuser->isManager() && $thisuser->getId()!=$ticket->getStaffId())
                    $errors['err']='Ticket já atribuído. Você não tem permissão para voltar a atribuir tickets atribuídos';
            }
            if(!$errors && $ticket->assignStaff($_POST['staffId'],$_POST['assign_message'])){
                $staff=$ticket->getStaff();
                $msg='Ticket designado para '.($staff?$staff->getName():'atendente');
                //Remove all the logs and go back to index page.
                TicketLock::removeStaffLocks($thisuser->getId(),$ticket->getId());
                $page='tickets.inc.php';
                $ticket=null;
            }elseif(!$errors['err']) {
                $errors['err']='Não é possível atribuir o ticket';
            }
            break; 
        case 'postnote':
            $fields=array();
            $fields['title']    = array('type'=>'string',   'required'=>1, 'error'=>'Título necessário');
            $fields['note']     = array('type'=>'string',   'required'=>1, 'error'=>'Nota/mensagem necessária');
            $params = new Validator($fields);
            if(!$params->validate($_POST))
                $errors=array_merge($errors,$params->errors());

            if(!$errors && $ticket->postNote($_POST['title'],$_POST['note'])){
                $msg='Nota interna enviada';
                if(isset($_POST['ticket_status']) && $_POST['ticket_status']){
                    if($ticket->setStatus($_POST['ticket_status']) && $ticket->reload()){
                        $msg.=' e estado definido como '.($ticket->isClosed()?'fechado':'aberto');
                        if($ticket->isClosed())
                            $page=$ticket=null; //Going back to main listing.
                    }
                }
            }elseif(!$errors['err']) {
                $errors['err']='Ocorreu um erro. Incapaz de publicar a nota.';
            }
            break;
        case 'update':
            $page='editticket.inc.php';
            if(!$ticket || !$thisuser->canEditTickets())
                $errors['err']='Permissão negada. Você não tem permissão para editar tickets';
            elseif($ticket->update($_POST,$errors)){
                $msg='Ticket atualizado com sucesso';
                $page='viewticket.inc.php';
            }elseif(!$errors['err']) {
                $errors['err']='Ocorreu um erro! Tente novamente.';
            }
            break;
        case 'process':
            $isdeptmanager=($ticket->getDeptId()==$thisuser->getDeptId())?true:false;
            switch(strtolower($_POST['do'])):
                case 'change_priority':
                    if(!$thisuser->canManageTickets() && !$thisuser->isManager()){
                        $errors['err']='Permissão Negada. Você não está autorizado a alterar ticket de prioridade';
                    }elseif(!$_POST['ticket_priority'] or !is_numeric($_POST['ticket_priority'])){
                        $errors['err']='Você deve selecionar a prioridade';
                    }
                    if(!$errors){
                        if($ticket->setPriority($_POST['ticket_priority'])){
                            $msg='Prioridade Alterada com Sucesso';
                            $ticket->reload();
                            $note='Prioridade do ticket definida para "'.$ticket->getPriority().'" por '.$thisuser->getName();
                            $ticket->logActivity('Prioridade Alterada',$note);
                        }else{
                            $errors['err']='Problemas para mudar prioridade. Tente novamente';
                        }
                    }
                    break;
                case 'close':
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Permissão Negada. Você não tem permissão para fechar ticket.';
                    }else{
                        if($ticket->close()){
                            $msg='Ticket #'.$ticket->getExtId().' estado definido como FECHADO';
                            $note='Ticket fechado sem resposta '.$thisuser->getName();
                            $ticket->logActivity('Ticket Fechado',$note);
                            $page=$ticket=null; //Going back to main listing.
                        }else{
                            $errors['err']='Problemas com o fechamento do ticket. Tente Novamente';
                        }
                    }
                    break;
                case 'reopen':
                    //if they can close...then assume they can reopen.
                    if(!$thisuser->isadmin() && !$thisuser->canCloseTickets()){
                        $errors['err']='Permissão Negada. Você não tem permissão para reabrir ticket.';
                    }else{
                        if($ticket->reopen()){
                            $msg='Estado do ticket definido como ABERTO';
                            $note='Ticket reaberto (sem comentários)';
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' e estado definido como '.$ticket->getPriority();
                            }
                            $note.=' por '.$thisuser->getName();
                            $ticket->logActivity('Ticket Reaberto',$note);
                        }else{
                            $errors['err']='Problemas para reabrir o ticket. Tente Novamente';
                        }
                    }
                    break;
                case 'release':
                    if(!($staff=$ticket->getStaff()))
                        $errors['err']='Ticket não é atribuído!';
                    elseif($ticket->release()) {
                        $msg='Ticket liberado (não atribuído) de '.$staff->getName().' por '.$thisuser->getName();;
                        $ticket->logActivity('Ticket não atribuído',$msg);
                    }else
                        $errors['err']='Problemas para liberar ticket. Tente Novamente';
                    break;
                case 'overdue':
                    //Mark the ticket as overdue
                    if(!$thisuser->isadmin() && !$thisuser->isManager()){
                        $errors['err']='Permissão Negada. Você não está autorizado a mudar o vencimento dos tickets';
                    }else{
                        if($ticket->markOverdue()){
                            $msg='Ticket marcado como vencido';
                            $note=$msg;
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' e estado definido como '.$ticket->getPriority();
                            }
                            $note.=' by '.$thisuser->getName();
                            $ticket->logActivity('Ticket Marcado Vencido',$note);
                        }else{
                            $errors['err']='Problemas para alterar o vencimento do ticket. Tente Novamente';
                        }
                    }
                    break;
                case 'banemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Permissão Negada. Você não tem permissão para banir e-mails';
                    }elseif(Banlist::add($ticket->getEmail(),$thisuser->getName())){
                        $msg='Email ('.$ticket->getEmail().') adicionado a lista de banidos';
                        if($ticket->isOpen() && $ticket->close()) {
                            $msg.=' & estado ticket definido para fechado';
                            $ticket->logActivity('Ticket Fechado',$msg);
                            $page=$ticket=null; //Going back to main listing.
                        }
                    }else{
                        $errors['err']='Não foi possível adicionar o e-mail para lista de banidos';
                    }
                    break;
                case 'unbanemail':
                    if(!$thisuser->isadmin() && !$thisuser->canManageBanList()){
                        $errors['err']='Permissão Negada. Você não tem permissão para remover e-mails da lista de banidos.';
                    }elseif(Banlist::remove($ticket->getEmail())){
                        $msg='Email removido da lista de banidos';
                    }else{
                        $errors['err']='Não foi possível remover o e-mail da ista de banidos. Tente Novamente.';
                    }
                    break;
                case 'delete': // Dude what are you trying to hide? bad customer support??
                    if(!$thisuser->isadmin() && !$thisuser->canDeleteTickets()){
                        $errors['err']='Permissão Negada. Você não tem permissão para APAGAR ticket!!';
                    }else{
                        if($ticket->delete()){
                            $page='tickets.inc.php'; //ticket is gone...go back to the listing.
                            $msg='Ticket Apagado Para Sempre';
                            $ticket=null; //clear the object.
                        }else{
                            $errors['err']='Problemas para deletar o ticket. Tente Novamente';
                        }
                    }
                    break;
                default:
                    $errors['err']='Você deve selecionar ação a ser executada';
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
                    $errors['err']='Você não tem permissão para gerenciar tickets. Fale com o administrador para obter acesso';    
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='Nenhum ticket selecionado. Você deve selecionar pelo menos um ticket.';
                elseif(($_POST['reopen'] || $_POST['close']) && !$thisuser->canCloseTickets())
                    $errors['err']='Você não tem permissão para fechar/reabrir tickets';
                elseif($_POST['delete'] && !$thisuser->canDeleteTickets())
                    $errors['err']='Você não tem permissão para apagar tickets';
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='Você deve selecionar pelo menos um ticket';
        
                if(!$errors) {
                    $count=count($_POST['tids']);
                    if(isset($_POST['reopen'])){
                        $i=0;
                        $note='Ticket reaberto pela '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->reopen()) {
                                $i++;
                                $t->logActivity('Ticket Reaberto',$note,false,'System');
                            }
                        }
                        $msg="$i of $count tickets selecionados reaberto";
                    }elseif(isset($_POST['close'])){
                        $i=0;
                        $note='Ticket fechado sem resposta por '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->close()){ 
                                $i++;
                                $t->logActivity('Ticket Fechado',$note,false,'System');
                            }
                        }
                        $msg="$i of $count tickets selecionados fechados";
                    }elseif(isset($_POST['overdue'])){
                        $i=0;
                        $note='Ticket marcado como vencido por '.$thisuser->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && !$t->isoverdue())
                                if($t->markOverdue()) { 
                                    $i++;
                                    $t->logActivity('Ticket Marcado Vencido',$note,false,'System');
                                }
                        }
                        $msg="$i of $count tickets selecionados marcados vencidos";
                    }elseif(isset($_POST['delete'])){
                        $i=0;
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->delete()) $i++;
                        }
                        $msg="$i of $count tickets selecionados apagados";
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
                    $errors['err']='Não é possível criar o bilhete. Corrija os erros e tente novamente';
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
    $nav->addSubMenu(array('desc'=>'Open ('.($stats['open']+$stats['answered']).')'
                            ,'title'=>'Tickets Abertos', 'href'=>'tickets.php', 'iconclass'=>'Ticket'));
}else{
    if($stats['open'])
        $nav->addSubMenu(array('desc'=>'Open ('.$stats['open'].')','title'=>'Tickets Abertos', 'href'=>'tickets.php', 'iconclass'=>'Ticket'));
    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Answered ('.$stats['answered'].')',
                           'title'=>'Tickets Respondidos', 'href'=>'tickets.php?status=answered', 'iconclass'=>'answeredTickets')); 
    }
}

if($stats['assigned']) {
    if(!$sysnotice && $stats['assigned']>10)
        $sysnotice=$stats['assigned'].' atribuído a você!';

    $nav->addSubMenu(array('desc'=>'My Tickets ('.$stats['assigned'].')','title'=>'Tickets Atribuídos',
                    'href'=>'tickets.php?status=assigned','iconclass'=>'assignedTickets'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'Overdue ('.$stats['overdue'].')','title'=>'Tickets Velhos',
                    'href'=>'tickets.php?status=overdue','iconclass'=>'overdueTickets'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' tickets vencidos!';
}

$nav->addSubMenu(array('desc'=>'Closed Tickets','title'=>'Tickets Fechados', 'href'=>'tickets.php?status=closed', 'iconclass'=>'closedTickets'));


if($thisuser->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'New Ticket','href'=>'tickets.php?a=open','iconclass'=>'newTicket'));    
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
