<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso negado');

//Get the config info.
$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfig());
//Basic checks for warnings...
$warn=array();
if($config['allow_attachments'] && !$config['upload_dir']) {
    $errors['allow_attachments']='Você precisará configurar para carregar diretório.';    
}else{
    if(!$config['allow_attachments'] && $config['allow_email_attachments'])
        $warn['allow_email_attachments']='*Attachments Disabled.';
    if(!$config['allow_attachments'] && ($config['allow_online_attachments'] or $config['allow_online_attachments_onlogin']))
        $warn['allow_online_attachments']='<br>*Attachments Disabled.';
}

if(!$errors['enable_captcha'] && $config['enable_captcha'] && !extension_loaded('gd'))
    $errors['enable_captcha']='Necessária GD captcha para trabalhar';
    

//Not showing err on post to avoid alarming the user...after an update.
if(!$errors['err'] &&!$msg && $warn )
    $errors['err']='Possíveis erros detectados, por favor verifique os avisos abaixo';
    
$gmtime=Misc::gmtime();
$depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1');
$templates=db_query('SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_TABLE.' WHERE cfg_id='.db_input($cfg->getId()));
?>
<div class="msg">Preferências do Sistema e Configurações&nbsp;&nbsp;(v<?=$config['ostversion']?>)</div>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
 <form action="admin.php?t=pref" method="post">
 <input type="hidden" name="t" value="pref">
 <tr><td>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header" ><td colspan=2>Definições Gerais</td></tr>
        <tr class="subheader">
            <td colspan=2">O modo offline irá desativar a interface do cliente e <b>apenas</b> permitirá o acesso ao Painel de Controle dos atendentes aos <b>super administradores</b></td>
        </tr>
        <tr><th><b>Ajuda de Estado</b></th>
            <td>
                <input type="radio" name="isonline"  value="1"   <?=$config['isonline']?'checked':''?> /><b>Online</b> (Ativado)
                <input type="radio" name="isonline"  value="0"   <?=!$config['isonline']?'checked':''?> /><b>Offline</b> (Desativado)
                &nbsp;<font class="warn">&nbsp;<?=$config['isoffline']?'osTicket offline':''?></font>
            </td>
        </tr>
        <tr><th>Ajuda de URL:</th>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?=$config['helpdesk_url']?>"> 
                &nbsp;<font class="error">*&nbsp;<?=$errors['helpdesk_url']?></font></td>
        </tr>
        <tr><th>Ajuda de Nome/Título:</th>
            <td><input type="text" size="40" name="helpdesk_title" value="<?=$config['helpdesk_title']?>"> </td>
        </tr>
        <tr><th>Modelos de Email Padrão:</th>
            <td>
                <select name="default_template_id">
                    <option value=0>Selecione Modelo Padrão</option>
                    <?
                    while (list($id,$name) = db_fetch_row($templates)){
                        $selected = ($config['default_template_id']==$id)?'SELECTED':''; ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$name?></option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['default_template_id']?></font>
            </td>
        </tr>
        <tr><th>Departamento Padrão:</th>
            <td>
                <select name="default_dept_id">
                    <option value=0>Selecione o departamento padrão</option>
                    <?
                    while (list($id,$name) = db_fetch_row($depts)){
                    $selected = ($config['default_dept_id']==$id)?'SELECTED':''; ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$name?> Departamento</option>
                    <?
                    }?>
                </select>&nbsp;<font class="error">*&nbsp;<?=$errors['default_dept_id']?></font>
            </td>
        </tr>
        <tr><th>Tamanho Padrão da Página:</th>
            <td>
                <select name="max_page_size">
                    <?
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?=$config['max_page_size'] == $i ? 'SELECTED':''?> value="<?=$i?>"><?=$i?></option>
                        <?
                    }?>
                </select>
            </td>
        </tr>
        <tr><th>Nível de log do sistema:</th>
            <td>
                <select name="log_level">
                    <option value=0 <?=$config['log_level'] == 0 ? 'selected="selected"':''?>>Nenhum (Desativar Logger)</option>
                    <option value=3 <?=$config['log_level'] == 3 ? 'selected="selected"':''?>> LIMPAR</option>
                    <option value=2 <?=$config['log_level'] == 2 ? 'selected="selected"':''?>> AVISAR</option>
                    <option value=1 <?=$config['log_level'] == 1 ? 'selected="selected"':''?>> ERRO</option>
                </select>
                &nbsp;Limpar os logs após
                <select name="log_graceperiod">
                    <option value=0 selected> Nenhum (Desabilitado)</option>
                    <?
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?=$config['log_graceperiod'] == $i ? 'SELECTED':''?> value="<?=$i?>"><?=$i?>&nbsp;<?=($i>1)?'Months':'Month'?></option>
                        <?
                    }?>
                </select>
            </td>
        </tr>
        <tr><th>Máximo de logins do atendente:</th>
            <td>
                <select name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_max_logins']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> tentativa(s) permitiu
                &nbsp;antes de uma
                <select name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['staff_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> min. tempo livre (pena em minutos)
            </td>
        </tr>
        <tr><th>Tempo limite de sessão atendente:</th>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?=$config['staff_session_timeout']?>">
                (<i>Tempo máximo do atendente inativo em minutos. Digite 0 para desativar o tempo limite.</i>)
            </td>
        </tr>
       <tr><th>Vincular sessão do atendente ao IP:</th>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?=$config['staff_ip_binding']?'checked':''?>>
               Vincular sessão do atendente ao IP de login.
            </td>
        </tr>

        <tr><th>Excesso de Logins do Client:</th>
            <td>
                <select name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_max_logins']==$i)?'selected="selected"':''),$i);
                    }

                    ?>
                </select> tentativa(s) permitida(s)
                &nbsp;antes de uma
                <select name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>',$i,(($config['client_login_timeout']==$i)?'selected="selected"':''),$i);
                    }
                    ?>
                </select> min. tempo limite (perda em minutos)
            </td>
        </tr>

        <tr><th>Tempo limite de sessão do cliente:</th>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?=$config['client_session_timeout']?>">
                (<i>Máximo de tempo ocioso do cliente em minutos. Digite 0 para desativar o tempo limite.</i>)
            </td>
        </tr>
        <tr><th>URLs clicáveis:</th>
            <td>
              <input type="checkbox" name="clickable_urls" <?=$config['clickable_urls']?'checked':''?>>
                Fazer URLs clicáveis
            </td>
        </tr>
        <tr><th>Habilitar Auto Cron:</th>
            <td>
              <input type="checkbox" name="enable_auto_cron" <?=$config['enable_auto_cron']?'checked':''?>>
                Habilitar o cron para chamar o atendente em atividade.
            </td>
        </tr>
    </table>
    
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Date &amp; Time</td></tr>
        <tr class="subheader">
            <td colspan=2>Por favor, consulte <a href="http://php.net/date" target="_blank">PHP Manual</a> para parâmetros suportados.</td>
        </tr>
        <tr><th>Formato do tempo:</th>
            <td>
                <input type="text" name="time_format" value="<?=$config['time_format']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['time_format']?></font>
                    <i><?=Format::date($config['time_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i></td>
        </tr>
        <tr><th>Formato da data:</th>
            <td><input type="text" name="date_format" value="<?=$config['date_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['date_format']?></font>
                        <i><?=Format::date($config['date_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Data &amp; Formato do tempo:</th>
            <td><input type="text" name="datetime_format" value="<?=$config['datetime_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['datetime_format']?></font>
                        <i><?=Format::date($config['datetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Dia, Data &amp; Formato do tempo:</th>
            <td><input type="text" name="daydatetime_format" value="<?=$config['daydatetime_format']?>">
                        &nbsp;<font class="error">*&nbsp;<?=$errors['daydatetime_format']?></font>
                        <i><?=Format::date($config['daydatetime_format'],$gmtime,$config['timezone_offset'],$config['enable_daylight_saving'])?></i>
            </td>
        </tr>
        <tr><th>Fuso horário padrão:</th>
            <td>
                <select name="timezone_offset">
                    <?
                    $gmoffset = date("Z") / 3600; //Server's offset.
                    echo"<option value=\"$gmoffset\">Server Time (GMT $gmoffset:00)</option>"; //Default if all fails.
                    $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                    while (list($offset,$tz) = db_fetch_row($timezones)){
                        $selected = ($config['timezone_offset'] ==$offset) ?'SELECTED':'';
                        $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)";
                        ?>
                        <option value="<?=$offset?>"<?=$selected?>><?=$tag?></option>
                        <?
                    }?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Horário de verão:</th>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?=$config['enable_daylight_saving'] ? 'checked': ''?>>Observar o horário de verão
            </td>
        </tr>
    </table>
   
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Opções de ticket &amp; Configurações</td></tr>
        <tr class="subheader"><td colspan=2>Se habilitar ticket fechado, começar auto-renovação na atividade do formulário.</td></tr>
        <tr><th valign="top">IDs dos tickets:</th>
            <td>
                <input type="radio" name="random_ticket_ids"  value="0"   <?=!$config['random_ticket_ids']?'checked':''?> /> Contínuo
                <input type="radio" name="random_ticket_ids"  value="1"   <?=$config['random_ticket_ids']?'checked':''?> />Aleatório  (recomendado)
            </td>
        </tr>
        <tr><th valign="top">Prioridade de ticket:</th>
            <td>
                <select name="default_priority_id">
                    <?
                    $priorities= db_query('SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE);
                    while (list($id,$tag) = db_fetch_row($priorities)){ ?>
                        <option value="<?=$id?>"<?=($config['default_priority_id']==$id)?'selected':''?>><?=$tag?></option>
                    <?
                    }?>
                </select> &nbsp;Prioridade padrão<br/>
                <input type="checkbox" name="allow_priority_change" <?=$config['allow_priority_change'] ?'checked':''?>>
                    Permitir ao usuário reescrever/configurar a prioridade (novos web tickets)<br/>
                <input type="checkbox" name="use_email_priority" <?=$config['use_email_priority'] ?'checked':''?> >
                    Usar a prioridade do e-mail quando disponível (novos tickets por e-mail)

            </td>
        </tr>
        <tr><th>Máximo de tickets <b>abertos</b>:</th>
            <td>
              <input type="text" name="max_open_tickets" size=4 value="<?=$config['max_open_tickets']?>"> 
                por e-mail. (<i>Ajuda com spam e controle de flood. Digite 0 para ilimitado.</i>)
            </td>
        </tr>
        <tr><th>Tempo de bloqueio automático:</td>
            <td>
              <input type="text" name="autolock_minutes" size=4 value="<?=$config['autolock_minutes']?>">
                 <font class="error"><?=$errors['autolock_minutes']?></font>
                (<i>Minutos para fechar um ticket em atividade. Digite 0 para desabilitar o fechamento.</i>)
            </td>
        </tr>
        <tr><th>Pedíodo de carência de tickets:</th>
            <td>
              <input type="text" name="overdue_grace_period" size=4 value="<?=$config['overdue_grace_period']?>">
                (<i>Horas antes o ticket é marcado em atraso. Digite 0 para desabilitar o envelhecimento.</i>)
            </td>
        </tr>
        <tr><th>Reabertura de tickets:</th>
            <td>
              <input type="checkbox" name="auto_assign_reopened_tickets" <?=$config['auto_assign_reopened_tickets'] ? 'checked': ''?>> 
                Atribuição automática de reabertura de tickets para o último demandado 'disponível'. (<i> limite de 3 meses</i>)
            </td>
        </tr>
        <tr><th>Atribuição de tickets:</th>
            <td>
              <input type="checkbox" name="show_assigned_tickets" <?=$config['show_assigned_tickets']?'checked':''?>>
                Mostrar tickets atribuídos para abrir fila.
            </td>
        </tr>
        <tr><th>Tickets respondidos:</th>
            <td>
              <input type="checkbox" name="show_answered_tickets" <?=$config['show_answered_tickets']?'checked':''?>>
                Mostrar tickets respondidos para abrir fila.
            </td>
        </tr>
        <tr><th>Log de atividade de ticket:</th>
            <td>
              <input type="checkbox" name="log_ticket_activity" <?=$config['log_ticket_activity']?'checked':''?>>
                Log de atividade de tickets como nota interna.
            </td>
        </tr>
        <tr><th>Identidade do atendente:</th>
            <td>
              <input type="checkbox" name="hide_staff_name" <?=$config['hide_staff_name']?'checked':''?>>
                Esconder nome do atendente na resposta.
            </td>
        </tr>
        <tr><th>Verificação pessoal:</th>
            <td>
                <?php
                   if($config['enable_captcha'] && !$errors['enable_captcha']) {?>
                        <img src="../captcha.php" border="0" align="left">&nbsp;
                <?}?>
              <input type="checkbox" name="enable_captcha" <?=$config['enable_captcha']?'checked':''?>>
                Habilitar captcha nos novos web tickets.&nbsp;<font class="error">&nbsp;<?=$errors['enable_captcha']?></font><br/>
            </td>
        </tr>

    </table>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2 >Configurações de e-mail</td></tr>
        <tr class="subheader"><td colspan=2>Observe que as configurações globais podem ser desabilitadas no nível departamento/e-mail.</td></tr>
        <tr><th valign="top"><br><b>E-mails recebidos</b>:</th>
            <td><i>Para e-mail (POP/IMAP) trabalhar você deve definir um cron job ou simplesmente habilitar o auto-cront</i><br/>
                <input type="checkbox" name="enable_mail_fetch" value=1 <?=$config['enable_mail_fetch']? 'checked': ''?>  > Ativar o POP / IMAP e-mail buscar
                    &nbsp;&nbsp;(<i>Configuração global que pode ser desabilitada no e-mail</i>) <br/>
                <input type="checkbox" name="enable_email_piping" value=1 <?=$config['enable_email_piping']? 'checked': ''?>  > Ativar canalização de e-mail
                   &nbsp;(<i>Canalizando, nós aceitamos política</i>)<br/>
                <input type="checkbox" name="strip_quoted_reply" <?=$config['strip_quoted_reply'] ? 'checked':''?>>
                    Tira resposta citada (<i>depende da tag abaixo</i>)<br/>
                <input type="text" name="reply_separator" value="<?=$config['reply_separator']?>"> Responde separador de tag
                &nbsp;<font class="error">&nbsp;<?=$errors['reply_separator']?></font>
            </td>
        </tr>
        <tr><th valign="top"><br><b>E-mails enviados</b>:</th>
            <td>
                <i><b>Default Email:</b> Somente se aplica para e-mails enviados sem configurações SMTP</i><br/>
                <select name="default_smtp_id"
                    onChange="document.getElementById('overwrite').style.display=(this.options[this.selectedIndex].value>0)?'block':'none';">
                    <option value=0>Selecionar um</option>
                    <option value=0 selected="selected">Nenhum: Use função de correio do PHP</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name,smtp_host FROM '.EMAIL_TABLE.' WHERE smtp_active=1');
                    if($emails && db_num_rows($emails)) {
                        while (list($id,$email,$name,$host) = db_fetch_row($emails)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            $email=sprintf('%s (%s)',$email,$host);
                            ?>
                            <option value="<?=$id?>"<?=($config['default_smtp_id']==$id)?'selected="selected"':''?>><?=$email?></option>
                        <?
                        }
                    }?>
                 </select>&nbsp;&nbsp;<font class="error">&nbsp;<?=$errors['default_smtp_id']?></font><br/>
                 <span id="overwrite" style="display:<?=($config['default_smtp_id']?'display':'none')?>">
                    <input type="checkbox" name="spoof_default_smtp" <?=$config['spoof_default_smtp'] ? 'checked':''?>>
                        Permitir falsificação (Não substituir).&nbsp;<font class="error">&nbsp;<?=$errors['spoof_default_smtp']?></font><br/>
                        </span>
             </td>
        </tr>
        <tr><th>Sistema de e-mail padrão:</th>
            <td>
                <select name="default_email_id">
                    <option value=0 disabled>Selecionar um</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE);
                    while (list($id,$email,$name) = db_fetch_row($emails)){ 
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?=$id?>"<?=($config['default_email_id']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?=$errors['default_email_id']?></font></td>
        </tr>
        <tr><th valign="top">E-mail de alerta padrão:</th>
            <td>
                <select name="alert_email_id">
                    <option value=0 disabled>Selecionar um</option>
                    <option value=0 selected="selected">Usar sistema de e-mail padrão (acima)</option>
                    <?
                    $emails=db_query('SELECT email_id,email,name FROM '.EMAIL_TABLE.' WHERE email_id != '.db_input($config['default_email_id']));
                    while (list($id,$email,$name) = db_fetch_row($emails)){
                        $email=$name?"$name &lt;$email&gt;":$email;
                        ?>
                     <option value="<?=$id?>"<?=($config['alert_email_id']==$id)?'selected':''?>><?=$email?></option>
                    <?
                    }?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?=$errors['alert_email_id']?></font>
                <br/><i>Usado para enviar outros alertas e notícias para o atendente.</i>
            </td>
        </tr>
        <tr><th>Sistema de administração de endereço de e-mail:</th>
            <td>
                <input type="text" size=25 name="admin_email" value="<?=$config['admin_email']?>">
                    &nbsp;<font class="error">*&nbsp;<?=$errors['admin_email']?></font></td>
        </tr>
    </table>

    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>Respostas automáticas &nbsp;(configuração global)</td></tr>
        <tr class="subheader"><td colspan=2">Esta é a configuração global que pode ser desativada a nível departamental.</td></tr>
        <tr><th valign="top">Novo ticket:</th>
            <td><i>Resposta automática inclui a identificação do ticket necessário para verificação do status do ticket</i><br>
                <input type="radio" name="ticket_autoresponder"  value="1"   <?=$config['ticket_autoresponder']?'checked':''?> />Habilitado
                <input type="radio" name="ticket_autoresponder"  value="0"   <?=!$config['ticket_autoresponder']?'checked':''?> />Desabilitado
            </td>
        </tr>
        <tr><th valign="top">Novo ticket pelo atendente:</th>
            <td><i>Notificação enviada quando o atendente cria um ticket em nome do usuário (atendente pode desativar)</i><br>
                <input type="radio" name="ticket_notice_active"  value="1"   <?=$config['ticket_notice_active']?'checked':''?> />Habilitado
                <input type="radio" name="ticket_notice_active"  value="0"   <?=!$config['ticket_notice_active']?'checked':''?> />Desabilitado
            </td>
        </tr>
        <tr><th valign="top">Nova mensagem:</th>
            <td><i>Mensagem anexada a uma confirmação de existência de ticket</i><br>
                <input type="radio" name="message_autoresponder"  value="1"   <?=$config['message_autoresponder']?'checked':''?> />Habilitado
                <input type="radio" name="message_autoresponder"  value="0"   <?=!$config['message_autoresponder']?'checked':''?> />Desabilitado
            </td>
        </tr>
        <tr><th valign="top">Aviso de limte:</th>
            <td><i>Ticket enviado, notica enviada <b>apenas uma vez</b> sobre a violação de limite para o usuário.</i><br/>               
                <input type="radio" name="overlimit_notice_active"  value="1"   <?=$config['overlimit_notice_active']?'checked':''?> />Habilitado
                <input type="radio" name="overlimit_notice_active"  value="0"   <?=!$config['overlimit_notice_active']?'checked':''?> />Desabilitado
                <br><i><b>Observação:</b> Administradores recebem alertas sobre TODAS as recusas por padrão.</i><br>
            </td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
        <tr class="header"><td colspan=2>&nbsp;Alertas &amp; Observações</td></tr>
        <tr class="subheader"><td colspan=2>
            Observações enviadas ao usuário usa-se 'nenhum e-mail de resposta' assim como aletas para atendente usa-se 'e-mail de alerta' estabelecido acima respectivamente a partir do endereço.</td>
        </tr>
        <tr><th valign="top">Alerta de novo ticket:</th>
            <td>
                <input type="radio" name="ticket_alert_active"  value="1"   <?=$config['ticket_alert_active']?'checked':''?> />Habilitado
                <input type="radio" name="ticket_alert_active"  value="0"   <?=!$config['ticket_alert_active']?'checked':''?> />Desabilitado
                <br><i>Selecionar destinatários</i>&nbsp;<font class="error">&nbsp;<?=$errors['ticket_alert_active']?></font><br>
                <input type="checkbox" name="ticket_alert_admin" <?=$config['ticket_alert_admin']?'checked':''?>> E-mail do administrador
                <input type="checkbox" name="ticket_alert_dept_manager" <?=$config['ticket_alert_dept_manager']?'checked':''?>> Gerenciamento de Departamentos
                <input type="checkbox" name="ticket_alert_dept_members" <?=$config['ticket_alert_dept_members']?'checked':''?>> Membros de departalmento (spammy)
            </td>
        </tr>
        <tr><th valign="top">Alerta de nova mensagem:</th>
            <td>
              <input type="radio" name="message_alert_active"  value="1"   <?=$config['message_alert_active']?'checked':''?> />Habilitado
              <input type="radio" name="message_alert_active"  value="0"   <?=!$config['message_alert_active']?'checked':''?> />Desabilitado
              <br><i>Selecionar destinatários</i>&nbsp;<font class="error">&nbsp;<?=$errors['message_alert_active']?></font><br>
              <input type="checkbox" name="message_alert_laststaff" <?=$config['message_alert_laststaff']?'checked':''?>> Último demandado
              <input type="checkbox" name="message_alert_assigned" <?=$config['message_alert_assigned']?'checked':''?>> Atendente atribuído
              <input type="checkbox" name="message_alert_dept_manager" <?=$config['message_alert_dept_manager']?'checked':''?>> Gerenciamento de Departamentos (spammy)
            </td>
        </tr>
        <tr><th valign="top">Alerta de nova nota interna:</th>
            <td>
              <input type="radio" name="note_alert_active"  value="1"   <?=$config['note_alert_active']?'checked':''?> />Habilitado
              <input type="radio" name="note_alert_active"  value="0"   <?=!$config['note_alert_active']?'checked':''?> />Desabilitado
              <br><i>Selecionar destinatários</i>&nbsp;<font class="error">&nbsp;<?=$errors['note_alert_active']?></font><br>
              <input type="checkbox" name="note_alert_laststaff" <?=$config['note_alert_laststaff']?'checked':''?>> Último demandado
              <input type="checkbox" name="note_alert_assigned" <?=$config['note_alert_assigned']?'checked':''?>> Atendente atribuído
              <input type="checkbox" name="note_alert_dept_manager" <?=$config['note_alert_dept_manager']?'checked':''?>> Gerenciamento de Departamentos (spammy)
            </td>
        </tr>
        <tr><th valign="top">Alerta de bilhetes em atraso:</th>
            <td>
              <input type="radio" name="overdue_alert_active"  value="1"   <?=$config['overdue_alert_active']?'checked':''?> />Habilitado
              <input type="radio" name="overdue_alert_active"  value="0"   <?=!$config['overdue_alert_active']?'checked':''?> />Desabilitado
              <br><i>E-maiol do administrador recebe um e-mail por padrão. Selecione mais destinatários abaixo</i>&nbsp;<font class="error">&nbsp;<?=$errors['overdue_alert_active']?></font><br>
              <input type="checkbox" name="overdue_alert_assigned" <?=$config['overdue_alert_assigned']?'checked':''?>> Atendente atribuído
              <input type="checkbox" name="overdue_alert_dept_manager" <?=$config['overdue_alert_dept_manager']?'checked':''?>> Gerenciamento de Departamentos
              <input type="checkbox" name="overdue_alert_dept_members" <?=$config['overdue_alert_dept_members']?'checked':''?>> Membros de departalmento (spammy)
            </td>
        </tr>
        <tr><th valign="top">Erros do sistema:</th>
            <td><i>Habilitar erros para serem eviados para o e-mail do administrador definido acima</i><br>
              <input type="checkbox" name="send_sys_errors" <?=$config['send_sys_errors']?'checked':'checked'?> disabled>Erros do sistema
              <input type="checkbox" name="send_sql_errors" <?=$config['send_sql_errors']?'checked':''?>>Erros de SQL
              <input type="checkbox" name="send_login_errors" <?=$config['send_login_errors']?'checked':''?>>Excessivas tentativas de entrada
            </td>
        </tr> 
        
    </table>
 </td></tr>
 <tr>
    <td style="padding:10px 0 10px 240px;">
        <input class="button" type="submit" name="submit" value="Salvar Mudanças">
        <input class="button" type="reset" name="reset" value="Redefinir Mudanças">
    </td>
 </tr>
 </form>
</table>
