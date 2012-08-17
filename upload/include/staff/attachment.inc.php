<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Acesso Negado');
//Get the config info.
$config=($errors && $_POST)?Format::input($_POST):$cfg->getConfig();
?>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
    <form action="admin.php?t=attach" method="post">
    <input type="hidden" name="t" value="attach">
    <tr>
      <td>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
          <tr class="header">
            <td colspan=2>&nbsp;Configurações de Anexo</td>
          </tr>
          <tr class="subheader">
            <td colspan=2">
                Antes de habilitar anexos certifique-se de compreender as configurações de segurança e questões relacionadas com uploads de arquivos.</td>
          </tr>
          <tr>
            <th width="165">Permitir anexos:</th>
            <td>
              <input type="checkbox" name="allow_attachments" <?=$config['allow_attachments'] ?'checked':''?>><b>Permitir Anexos</b>
                &nbsp; (<i>Configuração Global</i>)
                &nbsp;<font class="error">&nbsp;<?=$errors['allow_attachments']?></font>
            </td>
          </tr>
          <tr>
            <th>Anexos do e-mail:</th>
            <td>
                <input type="checkbox" name="allow_email_attachments" <?=$config['allow_email_attachments'] ? 'checked':''?> > Aceitar arquivos por e-mail
                    &nbsp;<font class="warn">&nbsp;<?=$warn['allow_email_attachments']?></font>
            </td>
          </tr>
         <tr>
            <th>Anexos online:</th>
            <td>
                <input type="checkbox" name="allow_online_attachments" <?=$config['allow_online_attachments'] ?'checked':''?> >
                    Permitir o upload de anexos on-line<br/>&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="allow_online_attachments_onlogin" <?=$config['allow_online_attachments_onlogin'] ?'checked':''?> >
                    Somente usuários autenticados. (<i>O usuário deve estar logado para fazer o upload de arquivos </i>)
                    <font class="warn">&nbsp;<?=$warn['allow_online_attachments']?></font>
            </td>
          </tr>
          <tr>
            <th>Arquivo resposta do atendente:</th>
            <td>
                <input type="checkbox" name="email_attachments" <?=$config['email_attachments']?'checked':''?> >Anexos para usuário de e-mail
            </td>
          </tr>
          <tr>
            <th nowrap>Tamanho máximo do arquivo:</th>
            <td>
              <input type="text" name="max_file_size" value="<?=$config['max_file_size']?>"> <i>bytes</i>
                <font class="error">&nbsp;<?=$errors['max_file_size']?></font>
            </td>
          </tr>
          <tr>
            <th>Diretório do anexo:</th>
            <td>
                Usuário de Web (apache por exemplo) deve ter acesso de gravação (WRITE) para a pasta. &nbsp;<font class="error">&nbsp;<?=$errors['upload_dir']?></font><br>
              <input type="text" size=60 name="upload_dir" value="<?=$config['upload_dir']?>"> 
              <font color=red>
              <?=$attwarn?>
              </font>
            </td>
          </tr>
          <tr>
            <th valign="top"><br/>Tipos de arquivos aceitos:</th>
            <td>
                Digite as extensões de arquivo permitidas separadas por uma vírgula. por exemplo <i>.doc, .pdf, </i> <br>
                Para aceitar todos os arquivos digite asterístico <b><i>.*</i></b>&nbsp;&nbsp;i.e dotStar (NÃO recomendado).
                <textarea name="allowed_filetypes" cols="21" rows="4" style="width: 65%;" wrap=HARD ><?=$config['allowed_filetypes']?></textarea>
            </td>
          </tr>
        </table>
    </td></tr>
    <tr><td style="padding:10px 0 10px 200px">
        <input class="button" type="submit" name="submit" value="Salvar Mudanças">
        <input class="button" type="reset" name="reset" value="Redefinir Mudanças">
    </td></tr>
  </form>
</table>
