<?php
/*********************************************************************
    profile.php

    Staff's profile handle

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require_once('staff.inc.php');
$msg='';
if($_POST && $_POST['id']!=$thisuser->getId()) { //Check dummy ID used on the form.
 $errors['err']='Erro Interno. Acesso Negado';
}

if(!$errors && $_POST) { //Handle post
    switch(strtolower($_REQUEST['t'])):
    case 'pref':
        if(!is_numeric($_POST['auto_refresh_rate']))
            $errors['err']='Invalid auto refresh value.';

        if(!$errors) {

            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',daylight_saving='.db_input(isset($_POST['daylight_saving'])?1:0).
                ',max_page_size='.db_input($_POST['max_page_size']).
                ',auto_refresh_rate='.db_input($_POST['auto_refresh_rate']).
                ',timezone_offset='.db_input($_POST['timezone_offset']).
                ' WHERE staff_id='.db_input($thisuser->getId());

            if(db_query($sql) && db_affected_rows()){
                $thisuser->reload();
                $_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
                $_SESSION['daylight']=$thisuser->observeDaylight();
                $msg='Preferências Atualizadas com Sucesso';
            }else{
                $errors['err']='Erro na atualização de Preferências.';
            }
        }
        break;
    case 'passwd':
        if(!$_POST['password'])
            $errors['password']='Senha atual';        
        if(!$_POST['npassword'])
            $errors['npassword']='Nova senha';
        elseif(strlen($_POST['npassword'])<6)
             $errors['npassword']='Deve ter pelo menos 6 caracteres';
        if(!$_POST['vpassword'])
            $errors['vpassword']='Confirme a nova senha';
        if(!$errors) {
            if(!$thisuser->check_passwd($_POST['password'])){
                $errors['password']='É necessária uma senha válida';
            }elseif(strcmp($_POST['npassword'],$_POST['vpassword'])){
                $errors['npassword']=$errors['vpassword']='Nova senha não corresponde';
            }elseif(!strcasecmp($_POST['password'],$_POST['npassword'])){
                $errors['npassword']='A nova senha é a antiga senha';
            }
        }
        if(!$errors) {       
            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',change_passwd=0, passwd='.db_input(MD5($_POST['npassword'])).
                ' WHERE staff_id='.db_input($thisuser->getId()); 
            if(db_query($sql) && db_affected_rows()){
                $msg='Senha Trocada com Sucesso';
            }else{
                $errors['err']='Não foi possível completar a troca de senha. Erro interno.';
            }
        }
        break;
    case 'info':
        //Update profile info
        if(!$_POST['firstname']) {
            $errors['firstname']='Primeiro nomeFirst name required';
        }
        if(!$_POST['lastname']) {
            $errors['lastname']='Último nomeLast name required';
        }
        if(!$_POST['email'] || !Validator::is_email($_POST['email'])) {
            $errors['email']='E-mail Válido';
        }
        if($_POST['phone'] && !Validator::is_phone($_POST['phone'])) {
            $errors['phone']='Digite um número válido';
        }
        if($_POST['mobile'] && !Validator::is_phone($_POST['mobile'])) {
            $errors['mobile']='Digite um número válido';
        }

        if($_POST['phone_ext'] && !is_numeric($_POST['phone_ext'])) {
            $errors['phone_ext']='Inválido.';
        }

        if(!$errors) {

            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',firstname='.db_input(Format::striptags($_POST['firstname'])).
                ',lastname='.db_input(Format::striptags($_POST['lastname'])).
                ',email='.db_input($_POST['email']).
                ',phone="'.db_input($_POST['phone'],false).'"'.
                ',phone_ext='.db_input($_POST['phone_ext']).
                ',mobile="'.db_input($_POST['mobile'],false).'"'.
                ',signature='.db_input(Format::striptags($_POST['signature'])).
                ' WHERE staff_id='.db_input($thisuser->getId());
            if(db_query($sql) && db_affected_rows()){
                $msg='Profile Updated Successfully';
            }else{
                $errors['err']='Erros ocorreram. Perfil NÃO atualizado';
            }
        }else{
            $errors['err']='Erros ocorreram abaixo. Tente novamente';
        }
        break;
    default:
        $errors['err']='Ação Desconhecida';
    endswitch;
    //Reload user info if no errors.
    if(!$errors) {
        $thisuser->reload();
        $_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
        $_SESSION['daylight']=$thisuser->observeDaylight();
    }
}

//Tab and Nav options.
$nav->setTabActive('profile');
$nav->addSubMenu(array('desc'=>'Perfil','href'=>'profile.php','iconclass'=>'user'));
$nav->addSubMenu(array('desc'=>'Preferências','href'=>'profile.php?t=pref','iconclass'=>'userPref'));
$nav->addSubMenu(array('desc'=>'Trocar Senha','href'=>'profile.php?t=passwd','iconclass'=>'userPasswd'));
//Warnings if any.
if($thisuser->onVacation()){
        $warn.='Bem vindo de volta! Você está listado como \'de férias\' por favor, deixe o administrador ou o gerente saber que você está de volta.';
}

$rep=($errors && $_POST)?Format::input($_POST):Format::htmlchars($thisuser->getData());

// page logic
$inc='myprofile.inc.php';
switch(strtolower($_REQUEST['t'])) {
    case 'pref':
        $inc='mypref.inc.php';
        break;
    case 'passwd':
        $inc='changepasswd.inc.php';
        break;
    case 'info':
    default:
        $inc='myprofile.inc.php';
}
//Forced password Change.
if($thisuser->forcePasswdChange()){
    $errors['err']='Você deve mudar a sua senha para continuar.';
    $inc='changepasswd.inc.php';
}

//Render the page.
require_once(STAFFINC_DIR.'header.inc.php');
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
<div>
   <? require(STAFFINC_DIR.$inc);  ?>
</div>
<?
require_once(STAFFINC_DIR.'footer.inc.php');
?>
