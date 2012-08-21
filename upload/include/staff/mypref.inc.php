<?php
if(!defined('OSTSCPINC') || !is_object($thisuser) || !$rep) die('Kwaheri');
?>
<div class="msg">&nbsp;Minhas Preferências</div>
<table width="100%" border="0" cellspacing=2 cellpadding=3>
 <form action="profile.php" method="post">
 <input type="hidden" name="t" value="pref">
 <input type="hidden" name="id" value="<?=$thisuser->getId()?>">
    <tr>
        <td width="145" nowrap>Tamanho máximo da página:</td>        
        <td>
            <select name="max_page_size">
                <?
                $pagelimit=$rep['max_page_size']?$rep['max_page_size']:$cfg->getPageSize();
                for ($i = 5; $i <= 50; $i += 5) {?>
                    <option <?=$pagelimit== $i ? 'SELECTED':''?>><?=$i?></option>
                <?}?>
            </select> Tickets/itens por página.
        </td>
    </tr>
    <tr>
        <td nowrap>Taxa de Auto-atualização:</td>
        <td>
            <input type="input" size=3 name="auto_refresh_rate" value="<?=$rep['auto_refresh_rate']?>">
            Em mins. (<i>Taxa de auto-atualização da página em minutos. Digite 0 para desativar</i>)
        </td>
    </tr>
    <tr>
        <td nowrap>Região Preferida:</td>
        <td>
            <select name="timezone_offset">
                <?
                $gmoffset  = date("Z") / 3600; //Server's offset.
                $currentoffset = ($rep['timezone_offset']==NULL)?$cfg->getTZOffset():$rep['timezone_offset'];
                echo"<option value=\"$gmoffset\">Server Time (GMT $gmoffset:00)</option>"; //Default if all fails.
                $timezones= db_query('SELECT offset,timezone FROM '.TIMEZONE_TABLE);
                while (list($offset,$tz) = db_fetch_row($timezones)){
                    $selected = ($currentoffset==$offset) ?'SELECTED':'';
                    $tag=($offset)?"GMT $offset ($tz)":" GMT ($tz)"; ?>
                    <option value="<?=$offset?>"<?=$selected?>><?=$tag?></option>
                <?}?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Horário de verão:</td>
        <td>
            <input type="checkbox" name="daylight_saving" <?=$rep['daylight_saving'] ? 'checked': ''?>>Observar o horário de verão
        </td>
    </tr>
   <tr><td>Hora Atual:</td>
        <td><b><i><?=Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$rep['timezone_offset'],$rep['daylight_saving'])?></i></b></td>
    </tr>  
    <tr>
        <td>&nbsp;</td>
        <td><br>
            <input class="button" type="submit" name="submit" value="Aplicar">
            <input class="button" type="reset" name="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="profile.php"'>
        </td>
    </tr>
 </form>
</table>
