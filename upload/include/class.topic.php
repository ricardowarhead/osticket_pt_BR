<?php
/*********************************************************************
    class.topic.php

    Help topic helper

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

/*
 * Mainly used as a helper...
 */

class Topic {
    var $id;
    var $topic;
    var $dept_id;
    var $priority_id;
    var $autoresp;
 
    var $info;
    
    function Topic($id,$fetch=true){
        $this->id=$id;
        if($fetch)
            $this->load();
    }

    function load() {

        if(!$this->id)
            return false;
        
        $sql='SELECT * FROM '.TOPIC_TABLE.' WHERE topic_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['topic_id'];
            $this->topic=$info['topic'];
            $this->dept_id=$info['dept_id'];
            $this->priority_id=$info['priority_id'];
            $this->active=$info['isactive'];
            $this->autoresp=$info['noautoresp']?false:true;
            $this->info=$info;
            return true;
        }
        $this->id=0;
        
        return false;
    }
  
    function reload() {
        return $this->load();
    }
    
    function getId(){
        return $this->id;
    }
    
    function getName(){
        return $this->topic;
    }
    
    function getDeptId() {
        return $this->dept_id;
    }

    function getPriorityId() {
        return $this->priority_id;
    }
    
    function autoRespond() {
        return $this->autoresp;
    }

    function isEnabled() {
         return $this->active?true:false;
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getInfo() {
        return $this->info;
    }

    function update($vars,&$errors) {
        if($this->save($this->getId(),$vars,$errors)){
            $this->reload();
            return true;
        }
        return false;
    }

    function create($vars,&$errors) { 
        return Topic::save(0,$vars,$errors);
    }

    function save($id,$vars,&$errors) {


        if($id && $id!=$vars['topic_id'])
            $errors['err']='Erro interno. Tente novamente.';

        if(!$vars['topic'])
            $errors['topic']='Tópico de ajuda requerido';
        elseif(strlen($vars['topic'])<5)
            $errors['topic']='Tema é muito curto. Mínimo de 5 caracteres';
        else{
            $sql='SELECT topic_id FROM '.TOPIC_TABLE.' WHERE topic='.db_input(Format::striptags($vars['topic']));
            if($id)
                $sql.=' AND topic_id!='.db_input($id);
            if(($res=db_query($sql)) && db_num_rows($res))
                $errors['topic']='Tópico já existe';
        }
            
        if(!$vars['dept_id'])
            $errors['dept_id']='Você deve selecionar um departamento';
            
        if(!$vars['priority_id'])
            $errors['priority_id']='Você deve selecionar uma prioridade';
            
        if(!$errors) {
            $sql='updated=NOW(),topic='.db_input(Format::striptags($vars['topic'])).
                 ',dept_id='.db_input($vars['dept_id']).
                 ',priority_id='.db_input($vars['priority_id']).
                 ',isactive='.db_input($vars['isactive']).
                 ',noautoresp='.db_input(isset($vars['noautoresp'])?1:0);
            if($id) {
                $sql='UPDATE '.TOPIC_TABLE.' SET '.$sql.' WHERE topic_id='.db_input($id);
                if(!db_query($sql) || !db_affected_rows())
                    $errors['err']='Não foi possível atualizar o tópico. Erro interno';
            }else{
                $sql='INSERT INTO '.TOPIC_TABLE.' SET '.$sql.',created=NOW()';
                if(!db_query($sql) or !($topicID=db_insert_id()))
                    $errors['err']='Não foi possível criar o tópico. Erro interno';
                else
                    return $topicID;
            }
        }

        return $errors?false:true;
    }
}
?>
