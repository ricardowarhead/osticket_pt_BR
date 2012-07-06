<?php
if(!defined('OSTCLIENTINC') || !is_object($ticket)) die('Kwaheri rafiki!'); //Say bye to our friend..

//Please customize the message below to fit your organization speak!
?>
<div>
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<div style="margin:5px 100px 100px 0;">
    <?=Format::htmlchars($ticket->getName())?>,<br>
    <p>
     Obrigado por nos contactar.<br>
     Uma solicitação ao suporte foi enviada e a Veezor retornará em breve.</p>
          
    <?if($cfg->autoRespONNewTicket()){ ?>
    <p>Um e-mail com o número do ticket foi enviado para <b><?=$ticket->getEmail()?></b>.
        Você irá precisar do número do ticket e seu e-mail para acessar o progresso da solicitação. 
    </p>
    <p>
     Se você quiser enviar comentários ou informações adicionais sobre o mesmo assunto, por favor siga as instruções contidas no e-mail.
    </p>
    <?}?>
    <p>Equipe de suporte :: Veezor Network Intelligence </p>
</div>
<?
unset($_POST); //clear to avoid re-posting on back button??
?>
