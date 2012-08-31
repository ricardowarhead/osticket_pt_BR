<?php
/*********************************************************************
    class.nav.php

    Navigation helper classes. Pointless BUT helps keep navigation clean and free from errors.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
class StaffNav {
    var $tabs=array();
    var $submenu=array();

    var $activetab;
    var $ptype;

    function StaffNav($pagetype='staff'){
        global $thisuser;

        $this->ptype=$pagetype;
        $tabs=array();
        if($thisuser->isAdmin() && strcasecmp($pagetype,'admin')==0) {
            $tabs['dashboard']=array('desc'=>'Painel de Controle','href'=>'admin.php?t=dashboard','title'=>'Painel de Controle');
            $tabs['settings']=array('desc'=>'Configurações','href'=>'admin.php?t=settings','title'=>'Configurações do Sistema');
            $tabs['emails']=array('desc'=>'E-mails','href'=>'admin.php?t=email','title'=>'Configurações de E-mail');
            $tabs['topics']=array('desc'=>'Tópicos de Ajuda','href'=>'admin.php?t=topics','title'=>'Tópicos de Ajuda');
            $tabs['staff']=array('desc'=>'Atendente','href'=>'admin.php?t=staff','title'=>'Membros');
            $tabs['depts']=array('desc'=>'Departamentos','href'=>'admin.php?t=depts','title'=>'Departamentos');
        }else {
            $tabs['tickets']=array('desc'=>'Tickets','href'=>'tickets.php','title'=>'Fila de Tickets');
            if($thisuser && $thisuser->canManageKb()){
             $tabs['kbase']=array('desc'=>'Respostas','href'=>'kb.php','title'=>'Base de Dados: Respostas');
            }
            $tabs['directory']=array('desc'=>'Comitiva','href'=>'directory.php','title'=>'Comissão Diretora');
            $tabs['profile']=array('desc'=>'Minha Conta','href'=>'profile.php','title'=>'Meu Perfil');
        }
        $this->tabs=$tabs;    
    }
    
    
    function setTabActive($tab){
            
        if($this->tabs[$tab]){
            $this->tabs[$tab]['active']=true;
            if($this->activetab && $this->activetab!=$tab && $this->tabs[$this->activetab])
                 $this->tabs[$this->activetab]['active']=false;
            $this->activetab=$tab;
            return true;
        }
        return false;
    }
    
    function addSubMenu($item,$tab=null) {
        
        $tab=$tab?$tab:$this->activetab;
        $this->submenu[$tab][]=$item;
    }

    
    
    function getActiveTab(){
        return $this->activetab;
    }        

    function getTabs(){
        return $this->tabs;
    }

    function getSubMenu($tab=null){
      
        $tab=$tab?$tab:$this->activetab;  
        return $this->submenu[$tab];
    }
    
}
?>
