<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin() || !is_object($template)) die('Acesso Negado');
$tpl=($errors && $_POST)?Format::input($_POST):Format::htmlchars($template->getInfo());
?>
<div class="msg">Modelos de E-mail</div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
  <form action="admin.php?t=templates" method="post">
    <input type="hidden" name="t" value="modelos">
    <input type="hidden" name="do" value="atualizar">
    <input type="hidden" name="id" value="<?=$template->getId()?>">
    <tr><td>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform tpl">
            <tr class="header"><td colspan=2 >Informações dos modelos</td></tr>
            <tr class="subheader"><td colspan=2><b>Última atualização em <?=Format::db_daydatetime($template->getUpdateDate())?></b></td></tr>
            <tr>
                <th>Nome</th>
                <td>
                    <input type="text" size="45" name="name" value="<?=$tpl['name']?>">
                            &nbsp;<font class="error">*&nbsp;<?=$errors['name']?></font></td>
            </tr>
            <tr>
                <th>Nota Interna:</th>
                <td><i>Notas Administrativas</i>&nbsp;<font class="error">&nbsp;<?=$errors['notes']?></font>
                    <textarea rows="5" cols="75" name="notes"><?=$tpl['notes']?></textarea>
                        &nbsp;<font class="error">&nbsp;<?=$errors['notes']?></font></td>
            </tr>
        </table>
        <div class="msg">Usuário</div>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform tpl">
            <tr class="header"><td colspan=2 >Resposta automática ao novo Ticket</td></tr>
            <tr class="subheader"><td colspan=2 >
                Resposta automática enviada ao usuário do novo Ticket.
                Destinado a dar ao utilizador o ID do Ticke, que pode ser utilizado para verificar seu status.</td>
                </tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_autoresp_subj" value="<?=$tpl['ticket_autoresp_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_autoresp_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="ticket_autoresp_body"><?=$tpl['ticket_autoresp_body']?></textarea>
                        &nbsp;<font class="error">&nbsp;<?=$errors['ticket_autoresp_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Nova mensagem automática</td></tr>
            <tr class="subheader"><td colspan=2 > 
                Confirmação enviada ao usurário quando uma nova mensagem é anexada a um Ticket existente. (respostas via e-mail e web)</td>
            </tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="message_autoresp_subj" value="<?=$tpl['message_autoresp_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_autoresp_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="message_autoresp_body"><?=$tpl['message_autoresp_body']?></textarea>
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_autoresp_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Aviso do novo Ticket</td></tr>
            <tr class="subheader"><td colspan=2 >
                Observação enviada para o usuário, se habilitado, <b>criado pelo atendente</b> em seu nome.</td>
                </tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_notice_subj" value="<?=$tpl['ticket_notice_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_notice_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="ticket_notice_body"><?=$tpl['ticket_notice_body']?></textarea>
                        &nbsp;<font class="error">&nbsp;<?=$errors['ticket_notice_body']?></font></td>
            </tr>
            <tr class="header"><td  colspan=2 >Aviso sobre limite de Tickets</td></tr>
            <tr class="subheader"><td colspan=2 >
                Aviso enviado quando o usuário atinge o máximo permitido de tickets abertos definido nas preferências.
                <br/>Administradores recebem notificações cada vez que um pedido de permissão de ajuda é negado.
            </td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_overlimit_subj" value="<?=$tpl['ticket_overlimit_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overlimit_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="ticket_overlimit_body"><?=$tpl['ticket_overlimit_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overlimit_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >&nbsp;Resposta do Ticket</td></tr>
            <tr class="subheader"><td colspan=2 >
                Modelo de mensagem enviada ao usuário quando ticket respondido ou alertar o usuário sobre uma resposta/disponibilidade de resposta.
            </td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_reply_subj" value="<?=$tpl['ticket_reply_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_reply_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</td>
                <td><textarea rows="7" cols="75" name="ticket_reply_body"><?=$tpl['ticket_reply_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_reply_body']?></font></td>
            </tr>
        </table>
        <span class="msg">Atendente</span>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform tpl">
            <tr class="header"><td colspan=2 >Alerta de Novo Ticket</td></tr>
            <tr class="subheader"><td colspan=2 >Alerta enviado ao atendente (se ativado) sobre novo ticket.</td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_alert_subj" value="<?=$tpl['ticket_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="ticket_alert_body"><?=$tpl['ticket_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Alerta de Nova Mensagem</td></tr>
            <tr class="subheader"><td colspan=2 >Alerta enviado ao atendente (se ativado) quando o usuário responde a um ticket existente.</td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="message_alert_subj" value="<?=$tpl['message_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['message_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="message_alert_body"><?=$tpl['message_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['message_alert_body']?></font></td>
            </tr>


            <tr class="header"><td colspan=2 >Alerta de Nova Nota Interna</td></tr>
            <tr class="subheader"><td colspan=2 >Alerta enviado para o atendente selecionado (se ativado) quando uma nota interna é anexada a um ticket.</td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="note_alert_subj" value="<?=$tpl['note_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['note_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="note_alert_body"><?=$tpl['note_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['note_alert_body']?></font></td>
            </tr>

            <tr class="header"><td colspan=2 >Alerta na Atribuição de Ticket</td></tr>
            <tr class="subheader"><td colspan=2 >Alerta enviado ao atendente na atribuição de ticket.</td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="assigned_alert_subj" value="<?=$tpl['assigned_alert_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['assigned_alert_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="assigned_alert_body"><?=$tpl['assigned_alert_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['assigned_alert_body']?></font></td>
            </tr>
            <tr class="header"><td colspan=2 >Alerta de Ticket Vencido/Atrasado</td></tr>
            <tr class="subheader"><td colspan=2 >Alerta enviado ao atendente sobre tickets vencidos ou em atraso.</td></tr>
            <tr>
                <th>Assunto</th>
                <td>
                    <input type="text" size="65" name="ticket_overdue_subj" value="<?=$tpl['ticket_overdue_subj']?>">
                            &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overdue_subj']?></font></td>
            </tr>
            <tr>
                <th>Mensagem:</th>
                <td><textarea rows="7" cols="75" name="ticket_overdue_body"><?=$tpl['ticket_overdue_body']?></textarea>
                    &nbsp;<font class="error">&nbsp;<?=$errors['ticket_overdue_body']?></font></td>
            </tr>
        </table>
    </td></tr>
    <tr><td style="padding-left:175px">
        <input class="button" type="submit" name="submit" value="Salvar Mudanças">
        <input class="button" type="reset" name="reset" value="Redefinir Mudanças">
        <input class="button" type="button" name="cancel" value="Cancelar Edição" onClick='window.location.href="admin.php?t=email"'></td>
    </tr>
  </form>
</table>
