<?php
/*********************************************************************
    kb.php

    Knowledge Base handle

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require('staff.inc.php');
if(!$thisuser->canManageKb() && !$thisuser->isadmin()) die('Acesso Negado');

$page='';
$answer=null; //clean start.
if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['id']) && is_numeric($id)) {
    $replyID=0;
    $resp=db_query('SELECT * FROM '.KB_PREMADE_TABLE.' WHERE premade_id='.db_input($id));
    if($resp && db_num_rows($resp))
        $answer=db_fetch_array($resp);
    else
        $errors['err']='Unknown ID#'.$id; //Sucker...invalid id
    
    if(!$errors && $answer['premade_id']==$id)
        $page='reply.inc.php';
}

if($_POST):
    $errors=array();
    switch(strtolower($_POST['a'])):
    case 'update':
    case 'add':
        if(!$_POST['id'] && $_POST['a']=='update')
            $errors['err']='ID do grupo está faltando ou é inválida';

        if(!$_POST['title'])
            $errors['title']='Título/Tema Exigido';
                
        if(!$_POST['answer'])
            $errors['answer']='Resposta Necessária';

        if(!$errors){
            $sql=' SET updated=NOW(),isenabled='.db_input($_POST['isenabled']).
                 ', dept_id='.db_input($_POST['dept_id']).
                 ', title='.db_input(Format::striptags($_POST['title'])).
                 ', answer='.db_input(Format::striptags($_POST['answer']));
            if($_POST['a']=='add'){ //create
                $res=db_query('INSERT INTO '.KB_PREMADE_TABLE.' '.$sql.',created=NOW()');
                if(!$res or !($replyID=db_insert_id()))
                    $errors['err']='Não foi possível criar a resposta. Erro interno';
                else
                    $msg='Resposta automática criada';
            }elseif($_POST['a']=='update'){ //update
                $res=db_query('UPDATE '.KB_PREMADE_TABLE.' '.$sql.' WHERE premade_id='.db_input($_POST['id']));
                if($res && db_affected_rows()){
                    $msg='Resposta automática atualizada';
                    $answer=db_fetch_array(db_query('SELECT * FROM '.KB_PREMADE_TABLE.' WHERE premade_id='.db_input($id)));
                }
                else
                    $errors['err']='Ocorreu um erro interno de atualização. Tente novamente';
            }
            if($errors['err'] && db_errno()==1062)
                $errors['title']='Título já existe!';
            
        }else{
            $errors['err']=$errors['err']?$errors['err']:'Ocorreram erros. Tente novamente';
        }
        break;
    case 'process':
        if(!$_POST['canned'] || !is_array($_POST['canned']))
            $errors['err']='Você deve selecionar pelo menos um item';
        else{
            $msg='';
            $ids=implode(',',$_POST['canned']);
            $selected=count($_POST['canned']);
            if(isset($_POST['enable'])) {
                if(db_query('UPDATE '.KB_PREMADE_TABLE.' SET isenabled=1,updated=NOW() WHERE isenabled=0 AND premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." de  $selected respostas selecionadas foram habilitadas";
            }elseif(isset($_POST['desabilitar'])) {
                if(db_query('UPDATE '.KB_PREMADE_TABLE.' SET isenabled=0, updated=NOW() WHERE isenabled=1 AND premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." de  $selected respostas selecionadas foram desativadas";
            }elseif(isset($_POST['deletar'])) {
                if(db_query('DELETE FROM '.KB_PREMADE_TABLE.' WHERE premade_id IN('.$ids.')'))
                    $msg=db_affected_rows()." de  $selected respostas selecionadas foram escluídas";
            }

            if(!$msg)
                $errors['err']='Ocorreu um erro. Tente novamente';
        }
        break;
    default:
        $errors['err']='Ação desconhecida';
    endswitch;
endif;
//new reply??
if(!$page && $_REQUEST['a']=='add' && !$replyID)
    $page='reply.inc.php';

    $inc=$page?$page:'premade.inc.php';

$nav->setTabActive('kbase');
$nav->addSubMenu(array('desc'=>'Respostas automáticas','href'=>'kb.php','iconclass'=>'premade'));
$nav->addSubMenu(array('desc'=>'Nova resposta automática','href'=>'kb.php?a=add','iconclass'=>'newPremade'));
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');

?>
