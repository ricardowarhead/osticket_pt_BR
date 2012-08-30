<?php
/*********************************************************************
    admin.php

    Handles all admin related pages....everything admin!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('staff.inc.php');
//Make sure the user is admin type LOCKDOWN BABY!
if(!$thisuser or !$thisuser->isadmin()){
    header('Location: index.php');
    require('index.php'); // just in case!
    exit;
}


//Some security related warnings - bitch until fixed!!! :)
if(defined('THIS_VERSION') && strcasecmp($cfg->getVersion(),THIS_VERSION)) {
    $sysnotice=sprintf('O script � a vers�o %s, enquanto o banco de dados � a vers�o %s.',THIS_VERSION,$cfg->getVersion());
    if(file_exists('../setup/'))
        $sysnotice.=' Possivelmente causada por estar imcompleta <a href="../setup/upgrade.php">upgrade</a>.';
    $errors['err']=$sysnotice; 
}elseif(!$cfg->isHelpDeskOffline()) {

    if(file_exists('../setup/')){
        $sysnotice='Por favor, espere um minuto para excluir <strong>setup/install</strong> este diret�rio por raz�es de seguran�a.';
    }else{

        if(CONFIG_FILE && file_exists(CONFIG_FILE) && is_writable(CONFIG_FILE)) {
            //Confirm for real that the file is writable by group or world.
            clearstatcache(); //clear the cache!
            $perms = @fileperms(CONFIG_FILE);
            if(($perms & 0x0002) || ($perms & 0x0010)) { 
                $sysnotice=sprintf('Por favor, mude a permiss�o de configura��o do arquivo (%s) para remover a escrita e o acesso. e.g <i>chmod 644 %s</i>',
                                basename(CONFIG_FILE),basename(CONFIG_FILE));
            }
        }

    }
    if(!$sysnotice && ini_get('register_globals'))
        $sysnotice='Por favor, cosidere desligar o registro global se poss�vel.';
}

//Access checked out OK...lets do the do 
define('OSTADMININC',TRUE); //checked by admin include files
define('ADMINPAGE',TRUE);   //Used by the header to swap menus.

//Files we might need.
//TODO: Do on-demand require...save some mem.
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.email.php');
require_once(INCLUDE_DIR.'class.mailfetch.php');

//Handle a POST.
if($_POST && $_REQUEST['t'] && !$errors):
    //print_r($_POST);
    //WELCOME TO THE HOUSE OF PAIN.
    $errors=array(); //do it anyways.

    switch(strtolower($_REQUEST['t'])):
        case 'pref':
            //Do the dirty work behind the scenes.
            if($cfg->updatePref($_POST,$errors)){
                $msg='Prefer�ncias atualizadas com sucesso';
                $cfg->reload();
            }else{
                $errors['err']=$errors['err']?$errors['err']:'Erro interno';
            }
            break;
        case 'attach':
            if($_POST['allow_attachments'] or $_POST['upload_dir']) {

                if($_POST['upload_dir']) //get the real path.
                    $_POST['upload_dir'] = realpath($_POST['upload_dir']);

                if(!$_POST['upload_dir'] or !is_writable($_POST['upload_dir'])) {
                    $errors['upload_dir']='Diret�rio deve ser v�lido e grav�vel';
                    if($_POST['allow_attachments'])
                        $errors['allow_attachments']='Diret�rio de upload inv�lido';
                }elseif(!ini_get('file_uploads')) {
                    $errors['allow_attachments']='O \'file_uploads\' direcionamento est� desabilitado em php.ini';
                }
                
                if(!is_numeric($_POST['max_file_size']))
                    $errors['max_file_size']='Tamanho m�ximo de arquivo exigido';

                if(!$_POST['allowed_filetypes'])
                    $errors['allowed_filetypes']='Extens�es de arquivos permitidos necess�ria';
            }
            if(!$errors) {
               $sql= 'UPDATE '.CONFIG_TABLE.' SET allow_attachments='.db_input(isset($_POST['allow_attachments'])?1:0).
                    ',upload_dir='.db_input($_POST['upload_dir']). 
                    ',max_file_size='.db_input($_POST['max_file_size']).
                    ',allowed_filetypes='.db_input(strtolower(preg_replace("/\n\r|\r\n|\n|\r/", '',trim($_POST['allowed_filetypes'])))).
                    ',email_attachments='.db_input(isset($_POST['email_attachments'])?1:0).
                    ',allow_email_attachments='.db_input(isset($_POST['allow_email_attachments'])?1:0).
                    ',allow_online_attachments='.db_input(isset($_POST['allow_online_attachments'])?1:0).
                    ',allow_online_attachments_onlogin='.db_input(isset($_POST['allow_online_attachments_onlogin'])?1:0).
                    ' WHERE id='.$cfg->getId();
               //echo $sql;
               if(db_query($sql)) {
                   $cfg->reload();
                   $msg='Configura��es de anexos atualizadas';
               }else{
                    $errors['err']='Erro de atualiza��o!';
               }
            }else {
                $errors['err']='Erro ocorrido. Consulte as mensagens de erro abaixo.';
                    
            }
            break;
        case 'api':
            include_once(INCLUDE_DIR.'class.api.php');
            switch(strtolower($_POST['do'])) {
                case 'add':
                    if(Api::add(trim($_POST['ip']),$errors))
                        $msg='Chave criada com sucesso por '.Format::htmlchars($_POST['ip']);
                    elseif(!$errors['err'])
                        $errors['err']='Erro ao adicionar IP. Tente novamente.';
                    break;
                case 'update_phrase':
                    if(Api::setPassphrase(trim($_POST['phrase']),$errors))
                        $msg='Dica de senha API atualizada com sucesso.';
                    elseif(!$errors['err'])
                        $errors['err']='Erro na atualiza��o da dica de senha. Tente novamente.';
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='Voc� deve selecionar pelo menos uma entrada para processar.';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',',$_POST['ids']);
                        if($_POST['enable'] || $_POST['disable']) {
                            $resp=db_query('UPDATE '.API_KEY_TABLE.' SET isactive='.db_input($_POST['enable']?1:0).' WHERE id IN ('.$ids.')');
                                
                            if($resp && ($i=db_affected_rows())){
                                $msg="$i de $count da(s) chave(s) selecionada(s) atualizada(s)";
                            }else {
                                $errors['err']='N�o � poss�vel excluir a(s) chave(s) selecionada(s).';
                             }
                        }elseif($_POST['delete']){
                            $resp=db_query('DELETE FROM '.API_KEY_TABLE.'  WHERE id IN ('.$ids.')');
                            if($resp && ($i=db_affected_rows())){
                                $msg="$i de $count da(s) chave(s) selecionadas exclu�das";
                            }else{
                                $errors['err']='N�o � poss�vel excluir a(s) chave(s) selecionadas. Tente novamente.';
                            }
                        }else {
                            $errors['err']='Comando desconhecido.';
                        }
                    }
                    break;
                default:
                    $errors['err']='A��o desconhecida '.$_POST['do'];
            }
            break;
        case 'banlist': //BanList.
            require_once(INCLUDE_DIR.'class.banlist.php');
            switch(strtolower($_POST['a'])) {
                case 'add':
                    if(!$_POST['email'] || !Validator::is_email($_POST['email']))
                        $errors['err']='Por favor, entre com um e-mail v�lido.';
                    elseif(BanList::isbanned($_POST['email']))
                        $errors['err']='E-mail j� banido';
                    else{
                        if(BanList::add($_POST['email'],$thisuser->getName()))
                            $msg='E-mail adicionado na banlist';
                        else
                            $errors['err']='N�o � poss�vel adicionar o e-mail na banlist. Tente novamente.';
                    }
                    break;
                case 'remove':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='Voc� deve selecionar pelo menos um e-mail.';
                    }else{
                        //TODO: move mass remove to Banlist class when needed elsewhere...at the moment this is the only place.
                        $sql='DELETE FROM '.BANLIST_TABLE.' WHERE id IN ('.implode(',',$_POST['ids']).')';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num de $count do(s) e-mail(s) selecionado(s) foi/foram removido(s) da banlist";
                        else
                            $errors['err']='N�o � poss�vel remover o(s) e-mail(s) selecionado(s). Tente novamente.';
                    }
                    break;
                default:
                    $errors['err']='Comando desconhecido da banlist';
            }
            break;
        case 'email':
            require_once(INCLUDE_DIR.'class.email.php');
            $do=strtolower($_POST['do']);
            switch($do){
                case 'update':
                    $email = new Email($_POST['email_id']);
                    if($email && $email->getId()) {
                        if($email->update($_POST,$errors))
                            $msg='E-mail atualizado com sucesso.';
                        elseif(!$errors['err'])
                            $errors['err']='Erro na atualiza��o de e-mail.';
                    }else{
                        $errors['err']='Erro interno.';
                    }
                    break;
                case 'create':
                    if(Email::create($_POST,$errors))
                        $msg='E-mail adicionado com sucesso.';
                    elseif(!$errors['err'])
                         $errors['err']='N�o � poss�vel adicionar e-mail. Erro interno.';
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='Voc� deve selecionar pelo menos um e-mail para processar.';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',',$_POST['ids']);
                        $sql='SELECT count(dept_id) FROM '.DEPT_TABLE.' WHERE email_id IN ('.$ids.') OR autoresp_email_id IN ('.$ids.')';
                        list($depts)=db_fetch_row(db_query($sql));
                        if($depts>0){
                            $errors['err']='Um ou mais e-mails selecionados est�o sendo usados por um Departamento. Remover a associa��o primeiro.';    
                        }elseif($_POST['delete']){
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if(Email::deleteEmail($v)) $i++;
                            }
                            if($i>0){
                                $msg="$i de $count do(s) e-mail(s) selecionado(s) foi/foram exclu�do(s).";
                            }else{
                                $errors['err']='N�o � poss�vel excluir o(s) e-mail(s) selecionado(s).';
                            }
                        }else{
                            $errors['err']='Comando desconhecido';
                        }
                    }
                    break;
                default:
                    $errors['err']='T�pico de a��o desconhecida';
            }
            break;
        case 'templates':
           include_once(INCLUDE_DIR.'class.msgtpl.php'); 
            $do=strtolower($_POST['do']);
            switch($do){
                case 'add':
                case 'create':
                    if(($tid=Template::create($_POST,$errors))){
                        $msg='Modelo criado com sucesso.';
                    }elseif(!$errors['err']){
                        $errors['err']='Erro na cria��o de modelo - tente novamente';
                    }
                    break;
                case 'update':
                    $template=null;
                    if($_POST['id'] && is_numeric($_POST['id'])) {
                        $template= new Template($_POST['id']);
                        if(!$template || !$template->getId()) {
                            $template=null;
                            $errors['err']='Modelo desconhecido'.$id;
                  
                        }elseif($template->update($_POST,$errors)){
                            $msg='Modelo atualizado com sucesso';
                        }elseif(!$errors['err']){
                            $errors['err']='Erro na atualiza��o de modelo. Tente novamente.';
                        }
                    }
                    break;
                case 'mass_process':
                    if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                        $errors['err']='Voc� deve selecionar pelo menos um modelo.';
                    }elseif(in_array($cfg->getDefaultTemplateId(),$_POST['ids'])){
                        $errors['err']='Voc� n�o pode excluir o modelo padr�o.';
                    }else{
                        $count=count($_POST['ids']);
                        $ids=implode(',',$_POST['ids']);
                        $sql='SELECT count(dept_id) FROM '.DEPT_TABLE.' WHERE tpl_id IN ('.$ids.')';
                        list($tpl)=db_fetch_row(db_query($sql));
                        if($tpl>0){
                            $errors['err']='Um ou mais modelos selecioados est�o sendo usados por um Departamento. Remover associa��o primeiro.';
                        }elseif($_POST['delete']){
                            $sql='DELETE FROM '.EMAIL_TEMPLATE_TABLE.' WHERE tpl_id IN ('.$ids.') AND tpl_id!='.db_input($cfg->getDefaultTemplateId());
                            if(($result=db_query($sql)) && ($i=db_affected_rows()))
                                $msg="$i de $count do(s) modelo(s) selecionado(s) foi/foram deletado(s).";
                            else
                                $errors['err']='N�o � poss�vel excluir o(s) modelo(s) selecionado(s).';
                        }else{
                            $errors['err']='Comando desconhecido.';
                        }
                    }
                    break;
                default:
                    $errors['err']='A��o desconhecida.';
                    //print_r($_POST);
            }
            break;
    case 'topics':
        require_once(INCLUDE_DIR.'class.topic.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $topic = new Topic($_POST['topic_id']);
                if($topic && $topic->getId()) {
                    if($topic->update($_POST,$errors))
                        $msg='T�pico atualizado com sucesso.';
                    elseif(!$errors['err'])
                        $errors['err']='Erro na atualizando o t�pico.';
                }else{
                    $errors['err']='Erro interno.';
                }
                break;
            case 'create':
                if(Topic::create($_POST,$errors))
                    $msg='T�pico de ajuda criado com sucesso.';
                elseif(!$errors['err'])
                    $errors['err']='N�o � poss�vel criar o t�pico. Erro interno.';
                break;
            case 'mass_process':
                if(!$_POST['tids'] || !is_array($_POST['tids'])) {
                    $errors['err']='Voc� deve selecionar pelo menos um t�pico.';
                }else{
                    $count=count($_POST['tids']);
                    $ids=implode(',',$_POST['tids']);
                    if($_POST['enable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=1, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=0 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num de $count do(s) servi�o(s) selecionado(s) foi/foram habilitado(s).";
                        else
                            $errors['err']='N�o � poss�vel completar a a��o.';
                    }elseif($_POST['disable']){
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=0, updated=NOW() WHERE topic_id IN ('.$ids.') AND isactive=1 ';
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num de $count do(s) t�pico(s) selecionado(s) foi/foram desativado(s).";
                        else
                            $errors['err']='N�o � poss�vel desativar o(s) t�pico(s) selecioado(s).';
                    }elseif($_POST['delete']){
                        $sql='DELETE FROM '.TOPIC_TABLE.' WHERE topic_id IN ('.$ids.')';        
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $msg="$num de $count do(s) t�pico(s) selecionado(s) foi/foram exclu�do(s)!";
                        else
                            $errors['err']='N�o � poss�vel excluir o(s) t�pico(s) selecionado(s).';
                    }
                }
                break;
            default:
                $errors['err']='A��o do t�pico desconhecida!';
        }
        break;
    case 'groups':
        include_once(INCLUDE_DIR.'class.group.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                if(Group::update($_POST['group_id'],$_POST,$errors)){
                    $msg='Group '.Format::htmlchars($_POST['group_name']).' atualizado com sucesso!';
                }elseif(!$errors['err']) {
                    $errors['err']='Erros ocorreram. Tente novamente.';
                }
                break;
            case 'create':
                if(($gID=Group::create($_POST,$errors))){
                    $msg='Group '.Format::htmlchars($_POST['group_name']).' criado com sucesso!';
                }elseif(!$errors['err']) {
                    $errors['err']='Erros ocorreram. Tente novamente.';
                }
                break;
            default:
                //ok..at this point..look WMA.
                if($_POST['grps'] && is_array($_POST['grps'])) {
                    $ids=implode(',',$_POST['grps']);
                    $selected=count($_POST['grps']);
                    if(isset($_POST['activate_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1,updated=NOW() WHERE group_enabled=0 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." de  $selected do(s) grupos selecionados foram habilitado.";
                    }elseif(in_array($thisuser->getDeptId(),$_POST['grps'])) {
                          $errors['err']="Tentando 'Desabilitar' ou 'Excluir' seu grupo? N�o faz o menor sentido!";
                    }elseif(isset($_POST['disable_grps'])) {
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() WHERE group_enabled=1 AND group_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." de  $selected do(s) grupos selecionados foram desativados."; 
                    }elseif(isset($_POST['delete_grps'])) {
                        $res=db_query('SELECT staff_id FROM '.STAFF_TABLE.' WHERE group_id IN('.$ids.')');
                        if(!$res || db_num_rows($res)) { //fail if any of the selected groups has users.
                            $errors['err']='Um ou mais grupos selecionados tem usu�rios. Somente grupos vazios podem ser exclu�dos.';
                        }else{
                            db_query('DELETE FROM '.GROUP_TABLE.' WHERE group_id IN('.$ids.')');    
                            $msg=db_affected_rows()." de  $selected do(s) grupos selecionados foram deletados";
                        }
                    }else{
                         $errors['err']='Comando desconhecido!';
                    }
                    
                }else{
                    $errors['err']='N�o foram selecionados grupos.';
                }
        }
    break;
    case 'staff':
        include_once(INCLUDE_DIR.'class.staff.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $staff = new Staff($_POST['staff_id']);
                if($staff && $staff->getId()) {
                    if($staff->update($_POST,$errors))
                        $msg='Perfil de atendente atualizado com sucesso!';
                    elseif(!$errors['err'])
                        $errors['err']='Erro atualizando o usu�rio.';
                }else{
                    $errors['err']='Erro interno.';
                }
                break;
            case 'create':
                if(($uID=Staff::create($_POST,$errors)))
                    $msg=Format::htmlchars($_POST['firstname'].' '.$_POST['lastname']).' adicionado com sucesso!';
                elseif(!$errors['err'])
                    $errors['err']='N�o � poss�vel adicionar o usu�rio. Erro interno.';
                break;
            case 'mass_process':
                //ok..at this point..look WMA.
                if($_POST['uids'] && is_array($_POST['uids'])) {
                    $ids=implode(',',$_POST['uids']);
                    $selected=count($_POST['uids']);
                    if(isset($_POST['enable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=1,updated=NOW() WHERE isactive=0 AND staff_id IN('.$ids.')';
                        db_query($sql);
                        $msg=db_affected_rows()." de  $selected do(s) usu�rios selecionados foram habilitados.";
                    
                    }elseif(in_array($thisuser->getId(),$_POST['uids'])) {
                        //sucker...watch what you are doing...why don't you just DROP the DB?
                        $errors['err']='Voc� n�o pode bloquear ou excluir voc� mesmo!';  
                    }elseif(isset($_POST['disable'])) {
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=0, updated=NOW() '.
                            ' WHERE isactive=1 AND staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId();
                        db_query($sql);
                        $msg=db_affected_rows()." de  $selected do(s) usu�rios selecionados foram bloqueados.";
                        //Release tickets assigned to the user?? NO? could be a temp thing 
                        // May be auto-release if not logged in for X days? 
                    }elseif(isset($_POST['delete'])) {
                        db_query('DELETE FROM '.STAFF_TABLE.' WHERE staff_id IN('.$ids.') AND staff_id!='.$thisuser->getId());
                        $msg=db_affected_rows()." de  $selected do(s) usu�rios selecionados foram excluidos";
                        //Demote the user 
                        db_query('UPDATE '.DEPT_TABLE.' SET manager_id=0 WHERE manager_id IN('.$ids.') ');
                        db_query('UPDATE '.TICKET_TABLE.' SET staff_id=0 WHERE staff_id IN('.$ids.') ');
                    }else{
                        $errors['err']='Comando desconhecido!';
                    }
                }else{
                    $errors['err']='N�o foram selecionados usu�rios.';
                }
            break;
            default:
                $errors['err']='Comando desconhecido!';
        }
    break;
    case 'dept':
        include_once(INCLUDE_DIR.'class.dept.php');
        $do=strtolower($_POST['do']);
        switch($do){
            case 'update':
                $dept = new Dept($_POST['dept_id']);
                if($dept && $dept->getId()) {
                    if($dept->update($_POST,$errors))
                        $msg='Departamento atualizado com sucesso!';
                    elseif(!$errors['err'])
                        $errors['err']='Erro atualizando o departamento.';
                }else{
                    $errors['err']='Erro interno.';
                }
                break;
            case 'create':
                if(($deptID=Dept::create($_POST,$errors)))
                    $msg=Format::htmlchars($_POST['dept_name']).' adicionado com sucesso.';
                elseif(!$errors['err'])
                    $errors['err']='N�o � poss�vel adicionar o departamento. Erro interno.';
                break;
            case 'mass_process':
                if(!$_POST['ids'] || !is_array($_POST['ids'])) {
                    $errors['err']='Voc� deve selecionar pelo menos um departamento.';
                }elseif(!$_POST['public'] && in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                    $errors['err']='Voc� n�o pode desativar/excluir um departamento padr�o. Remova o departamento padr�o e tente novamente.';
                }else{
                    $count=count($_POST['ids']);
                    $ids=implode(',',$_POST['ids']);
                    if($_POST['public']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 WHERE dept_id IN ('.$ids.')';  
                        if(db_query($sql) && ($num=db_affected_rows()))
                            $warn="$num de $count do(s)departamento(s) selecionando(s) tornaram-se p�blicos.";
                        else
                            $errors['err']='N�o � poss�vel fazer departamentos p�blicos.';
                    }elseif($_POST['private']){
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0 WHERE dept_id IN ('.$ids.') AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            $warn="$num of $count do(s) departamento(s) selecionando(s) tornaram-se privados.";
                        }else
                            $errors['err']='N�o � poss�vel fazer os departamentos selecionados serem privados. Possivelmente j� s�o privados!';
                            
                    }elseif($_POST['delete']){
                        //Deny all deletes if one of the selections has members in it.
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($members)=db_fetch_row(db_query($sql));
                        $sql='SELECT count(topic_id) FROM '.TOPIC_TABLE.' WHERE dept_id IN ('.$ids.')';
                        list($topics)=db_fetch_row(db_query($sql));
                        if($members){
                            $errors['err']='N�o pode excluir departamento com membros. Mova os atendentes primeiro.';
                        }elseif($topic){
                             $errors['err']='N�o pode excluir departamento assiciados com t�picos de ajuda. Remova a associa��o primeiro.';
                        }else{
                            //We have to deal with individual selection because of associated tickets and users.
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v==$cfg->getDefaultDeptId()) continue; //Don't delete default dept. Triple checking!!!!!
                                if(Dept::delete($v)) $i++;
                            }
                            if($i>0){
                                $warn="$i de $count do(s) departamento(s) selecionado(s) foi/foram exclu�do(s).";
                            }else{
                                $errors['err']='N�o � poss�vel excluir os departamentos selecionados.';
                            }
                        }
                    }
                }
            break;            
            default:
                $errors['err']='A��o do departamento desconhecida';
        }
    break;
    default:
        $errors['err']='Comando desconhecido!';
    endswitch;
endif;

//================ADMIN MAIN PAGE LOGIC==========================
//Process requested tab.
$thistab=strtolower($_REQUEST['t']?$_REQUEST['t']:'dashboard');
$inc=$page=''; //No outside crap please!
$submenu=array();
switch($thistab){
    //Preferences & settings
    case 'settings':
    case 'pref':
    case 'attach':
    case 'api':
        $nav->setTabActive('settings');
        $nav->addSubMenu(array('desc'=>'Prefer�cias','href'=>'admin.php?t=pref','iconclass'=>'preferences'));
        $nav->addSubMenu(array('desc'=>'Anexos','href'=>'admin.php?t=attach','iconclass'=>'attachment'));
        $nav->addSubMenu(array('desc'=>'API','href'=>'admin.php?t=api','iconclass'=>'api'));
        switch($thistab):
        case 'settings':            
        case 'pref':        
            $page='preference.inc.php';
            break;
        case 'attach':
            $page='attachment.inc.php';
            break;
        case 'api':
            $page='api.inc.php';
        endswitch;
        break;   
    case 'dashboard':
    case 'syslog':
        $nav->setTabActive('dashboard');
        $nav->addSubMenu(array('desc'=>'Logs do sistema','href'=>'admin.php?t=syslog','iconclass'=>'syslogs'));
        $page='syslogs.inc.php';
        break;
    case 'email':
    case 'templates':
    case 'banlist':
        $nav->setTabActive('emails');
        $nav->addSubMenu(array('desc'=>'Endereços de e-mail','href'=>'admin.php?t=email','iconclass'=>'emailSettings'));
        $nav->addSubMenu(array('desc'=>'Adicionar novo e-mail','href'=>'admin.php?t=email&a=new','iconclass'=>'newEmail'));
        $nav->addSubMenu(array('desc'=>'Modelos','href'=>'admin.php?t=templates','title'=>'Email Templates','iconclass'=>'emailTemplates')); 
        $nav->addSubMenu(array('desc'=>'Banlist/Proibidos','href'=>'admin.php?t=banlist','title'=>'Banned Email','iconclass'=>'banList')); 
        switch(strtolower($_REQUEST['t'])){
            case 'templates':
                $page='templates.inc.php';
                $template=null;
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['email_id']) && is_numeric($id)) {
                    include_once(INCLUDE_DIR.'class.msgtpl.php');
                    $template= new Template($id);
                    if(!$template || !$template->getId()) {
                        $template=null;
                        $errors['err']='Incapaz de buscar a informa��o do modelo ID#'.$id;
                    }else {
                        $page='template.inc.php';
                    }
                }
                break;
            case 'banlist':
                $page='banlist.inc.php';
                break;
            case 'email':
            default:
                include_once(INCLUDE_DIR.'class.email.php');
                $email=null;
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['email_id']) && is_numeric($id)) {
                    $email= new Email($id,false);
                    if(!$email->load()) {
                        $email=null;
                        $errors['err']='Incapaz de buscar a informa��o no e-mail ID#'.$id;
                    }
                }
                $page=($email or ($_REQUEST['a']=='new' && !$emailID))?'email.inc.php':'emails.inc.php';
        }
        break;
    case 'topics':
        require_once(INCLUDE_DIR.'class.topic.php');
        $topic=null;
        $nav->setTabActive('topics');
        $nav->addSubMenu(array('desc'=>'T�picos de ajuda','href'=>'admin.php?t=topics','iconclass'=>'helpTopics'));
        $nav->addSubMenu(array('desc'=>'Adicionar novo t�pico de ajuda','href'=>'admin.php?t=topics&a=new','iconclass'=>'newHelpTopic'));
        if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['topic_id']) && is_numeric($id)) {
            $topic= new Topic($id);
            if(!$topic->load() && $topic->getId()==$id) {
                $topic=null;
                $errors['err']='Incapaz de buscar a informa��o no t�pico #'.$id;
            }
        }
        $page=($topic or ($_REQUEST['a']=='new' && !$topicID))?'topic.inc.php':'helptopics.inc.php';
        break;
    //Staff (users, groups and teams)
    case 'grp':
    case 'groups':
    case 'staff':
        $group=null;
        //Tab and Nav options.
        $nav->setTabActive('staff');
        $nav->addSubMenu(array('desc'=>'Membros atendentes','href'=>'admin.php?t=staff','iconclass'=>'users'));
        $nav->addSubMenu(array('desc'=>'Adicionar novo usu�rio','href'=>'admin.php?t=staff&a=new','iconclass'=>'newuser'));
        $nav->addSubMenu(array('desc'=>'Usu�rios dos grupos','href'=>'admin.php?t=groups','iconclass'=>'groups'));
        $nav->addSubMenu(array('desc'=>'Adicionar novo grupo','href'=>'admin.php?t=groups&a=new','iconclass'=>'newgroup'));
        $page='';
        switch($thistab){
            case 'grp':
            case 'groups':
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['group_id']) && is_numeric($id)) {
                    $res=db_query('SELECT * FROM '.GROUP_TABLE.' WHERE group_id='.db_input($id));
                    if(!$res or !db_num_rows($res) or !($group=db_fetch_array($res)))
                        $errors['err']='Incapaz de buscar a informa��o no grupo ID#'.$id;
                }
                $page=($group or ($_REQUEST['a']=='new' && !$gID))?'group.inc.php':'groups.inc.php';
                break;
            case 'staff':
                $page='staffmembers.inc.php';
                if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['staff_id']) && is_numeric($id)) {
                    $staff = new Staff($id);
                    if(!$staff || !is_object($staff) || $staff->getId()!=$id) {
                        $staff=null;
                        $errors['err']='Incapaz de buscar a informa��o no rep ID#'.$id;
                    }
                }
                $page=($staff or ($_REQUEST['a']=='new' && !$uID))?'staff.inc.php':'staffmembers.inc.php';
                break;
            default:
                $page='staffmembers.inc.php';
        }
        break;
    //Departments
    case 'dept': //lazy
    case 'depts':
        $dept=null;
        if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['dept_id']) && is_numeric($id)) {
            $dept= new Dept($id);
            if(!$dept || !$dept->getId()) {
                $dept=null;
                $errors['err']='Incapaz de buscar a informa��o no Departamento ID#'.$id;
            }
        }
        $page=($dept or ($_REQUEST['a']=='new' && !$deptID))?'dept.inc.php':'depts.inc.php';
        $nav->setTabActive('depts');
        $nav->addSubMenu(array('desc'=>'Departmentos','href'=>'admin.php?t=depts','iconclass'=>'departments'));
        $nav->addSubMenu(array('desc'=>'Adicionar novo departamento','href'=>'admin.php?t=depts&a=new','iconclass'=>'newDepartment'));
        break;
    // (default)
    default:
        $page='pref.inc.php';
}
//========================= END ADMIN PAGE LOGIC ==============================//

$inc=($page)?STAFFINC_DIR.$page:'';
//Now lets render the page...
require(STAFFINC_DIR.'header.inc.php');
?>
<div>
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p align="center" id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<table width="100%" border="0" cellspacing="0" cellpadding="1">
    <tr><td>
        <div style="margin:0 5px 5px 5px;">
        <?
            if($inc && file_exists($inc)){
                require($inc);
            }else{
                ?>
                <p align="center">
                    <font class="error">Problemas ao carregar a p�gina de administra��o solicitada (<?=Format::htmlchars($thistab)?>)</font>
                    <br>Possivelmente o acesso foi negado, se voc� acredita que isto � um erro por favor contate o suporte t�cnico.
                </p>
            <?}?>
        </div>
    </td></tr>
</table>
<?
include_once(STAFFINC_DIR.'footer.inc.php');
?>
