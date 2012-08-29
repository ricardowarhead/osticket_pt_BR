<?php
/*********************************************************************
    class.http.php

    Http helper.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
class Http {
    
    function header_code_verbose($code) {
        switch($code):
        case 200: return '200 OK';
        case 204: return '204 Sem Conteúdo';
        case 401: return '401 Não Autorizado';
        case 403: return '403 Proibido';
        case 405: return '405 Método Não Permitido';
        case 416: return '416 Intervalo Solicitado Não Satisfatório';
        default:  return '500 Erro Interno do Servidor';
        endswitch;
    }
    
    function response($code,$content,$contentType='text/html',$charset='UTF-8') {
		
        header('HTTP/1.1 '.Http::header_code_verbose($code));
		header('Situação: '.Http::header_code_verbose($code)."\r\n");
		header("Conexão: Fechada\r\n");
		header("Tipo de conteúdo: $contentType; charset=$charset\r\n");
        header('Tamanho do conteúdo: '.strlen($content)."\r\n\r\n");
       	print $content;
        exit;
    }
	
	function redirect($url,$delay=0,$msg='') {

        if(strstr($_SERVER['SERVER_SOFTWARE'], 'IIS')){
            header("Atualização: $delay; URL=$url");
        }else{
            header("Localização: $url");
        }
        exit;
    }
}
?>
