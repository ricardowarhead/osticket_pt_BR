<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require('client.inc.php');
//We are only showing landing page to users who are not logged in.
if($thisclient && is_object($thisclient) && $thisclient->isValid()) {
    require('tickets.php');
    exit;
}


require(CLIENTINC_DIR.'header.inc.php');
?>
<div id="index">
<h1>Bem vindo ao centro de suporte e atendimento</h1>
<p class="big">A fim de agilizar as solicitações de suporte e melhor atendê-lo, nós utilizamos um sistema de ticket de suporte. Cada pedido de suporte é atribuído um número de bilhete único que você pode usar para rastrear o progresso e respostas on-line. Para sua garantia, nós fornecemos arquivos completos e histórico de todos os seus pedidos de suporte. É necessário um endereço de e-mail válido.</p>
<hr />
<br />
<div class="lcol">
  <img src="./images/new_ticket_icon.jpg" width="48" height="48" align="left" style="padding-bottom:150px;">
  <h3>Abertura de um novo ticket</h3>
  Forneça o máximo de detalhes possível para que podemos ajudá-lo melhor. Para atualizar um ticket apresentado anteriormente, por favor use o formulário à direita.
  <br /><br />
  <form method="link" action="open.php">
  <input type="submit" class="button2" value="Abrir um Novo Ticket">
  </form>
</div>
<div class="rcol">
  <img src="./images/ticket_status_icon.jpg" width="48" height="48" align="left" style="padding-bottom:150px;">
  <h3>Checar Situação de Ticket</h3>Nós fornecemos arquivos e histórico de todos os seus pedidos de suporte completo com respostas.
  <br /><br />
  <form class="status_form" action="login.php" method="post">
    <fieldset>
      <label>E-mail:</label>
      <input type="text" name="lemail">
    </fieldset>
    <fieldset>
     <label>Ticket#:</label>
     <input type="text" name="lticket">
    </fieldset>
    <fieldset>
        <label>&nbsp;</label>
         <input type="submit" class="button2" value="Checar Situação">
    </fieldset>
  </form>
</div>
<div class="clear"></div>
<br />
</div>
<br />
<?require(CLIENTINC_DIR.'footer.inc.php'); ?>
