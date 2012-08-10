<?php
/*********************************************************************
    open.php

    New tickets handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('client.inc.php');
define('SOURCE','Web'); //Ticket source.
$inc='open.inc.php';    //default include.
$errors=array();
if($_POST):
    $_POST['deptId']=$_POST['emailId']=0; //Just Making sure we don't accept crap...only topicId is expected.
    if(!$thisuser && $cfg->enableCaptcha()){
        if(!$_POST['captcha'])
            $errors['captcha']='Digite o texto mostrado na imagem.';
        elseif(strcmp($_SESSION['captcha'],md5($_POST['captcha'])))
            $errors['captcha']='Invalido - tente novamente!';
    }
    //Ticket::create...checks for errors..
    if(($ticket=Ticket::create($_POST,$errors,SOURCE))){
        $msg='Solicitação de ticker de suporte criada';
        if($thisclient && $thisclient->isValid()) //Logged in...simply view the newly created ticket.
            @header('Location: tickets.php?id='.$ticket->getExtId());
        //Thank the user and promise speedy resolution!
        $inc='thankyou.inc.php';
    }else{
        $errors['err']=$errors['err']?$errors['err']:'Não foi possível criar um ticket. Por favor, corrija os erros abaixo e tente novamente!';
    }
endif;

//page
require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.$inc);
require(CLIENTINC_DIR.'footer.inc.php');
?>
