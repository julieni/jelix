<?php
/**
* @package     testapp
* @subpackage  testapp module
* @version     $Id$
* @author      Laurent Jouanneau
* @contributor
* @copyright   2005-2006 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class formsCtrl extends jController {

    function listform(){
        $rep = $this->getResponse('html');
        $rep->title = 'Instances list of forms';
        $rep->body->assign('page_title','Instances list of forms');

        $tpl = new jTpl();
        // on triche ici, il n'y a pas d'api car inutile en temps normal
        if(isset($_SESSION['JFORMS']['sample2']))
            $tpl->assign('liste', $_SESSION['JFORMS']['sample2']);
        else
            $tpl->assign('liste', array()); 
        $rep->body->assign('MAIN',$tpl->fetch('forms_liste'));
        return $rep;
    }

    /**
     * creation d'un nouveau formulaire vierge
     * et redirection vers le formulaire html
     */
    function newform(){
        // création d'un formulaire vierge
        $form = jForms::create('sample2');
        $rep= $this->getResponse("redirect");
        $rep->action="forms:showform";
        $rep->params['id']= $form->id();
        return $rep;
    }

    /**
     * creation d'un formulaire avec des données initialisé à partir d'un enregistrement (factice)
     * et redirection vers le formulaire html
     */
    function edit(){
        $id = $this->param('id');
        $form = jForms::create('sample2', $this->param('id'));
        // remplissage du formulaire. Ici on le fait à la main, mais ça pourrait
        // être à partir d'un dao
        if($id == 1){
            $form->setData('nom','Dupont');
            $form->setData('prenom','Laurent');
        }elseif($id == 2){
            $form->setData('nom','Durant');
            $form->setData('prenom','George');
        }else{
            $form->setData('nom','inconnu');
            $form->setData('prenom','inconnu');
        }
    
        // redirection vers le formulaire
        $rep= $this->getResponse("redirect");
        $rep->action="forms:showform";
        $rep->params['id']= $form->id(); // ou $id, c'est pareil
        return $rep;
    }



    /**
     * affichage du formulaire html
     */
    function showform(){
        $rep = $this->getResponse('html');
        $rep->title = 'Form editing';
        $rep->body->assign('page_title', 'forms');

        // recupère les données du formulaire dont l'id est dans le paramètre id
        $form = jForms::get('sample2', $this->param('id'));
        if ($form) {
            $tpl = new jTpl();
            $tpl->assign('form', $form->getContainer());
            $tpl->assign('id', $form->id());
            if ($form->securityLevel != jFormsBase::SECURITY_LOW)
              $tpl->assign('token', $form->createNewToken());
            else
              $tpl->assign('token','');
            $rep->body->assign('MAIN',$tpl->fetch('forms_edit'));
        }else{
            $rep->body->assign('MAIN','<p>bad id</p>' );
        }

        return $rep;
    }

    function save(){

        $id = $this->param('id');
        $newid = $this->param('newid');

        // récupe le formulaire et le rempli avec les données reçues de la requête
        $form = jForms::fill('sample2', $id);

        if($id != $newid){
            $form2 = jForms::create('sample2', $newid);
            $form2->getContainer()->data = $form->getContainer()->data;
        }
        
        if ($id == '0') {
           jForms::destroy('sample2', $id);
        }

        // on pourrait ici enregistrer les données aprés un $form->check()
        // non implementé pour le moment...

        $rep= $this->getResponse("redirect");
        $rep->action="forms:listform";
        return $rep;
    }

    function view(){
        $form = jForms::get('sample2',$this->param('id'));
        $rep = $this->getResponse('html');
        $rep->title = 'Content of a form';
        $rep->body->assign('page_title','forms');

        if($form){
            $tpl = new jTpl();
            $tpl->assign('nom', $form->getData('nom'));
            $tpl->assign('prenom', $form->getData('prenom'));
            $tpl->assign('id', $this->param('id'));
            $rep->body->assign('MAIN',$tpl->fetch('forms_view'));
        }else{
            $rep->body->assign('MAIN','<p>The form doesn\'t exist.</p>');
        }
        return $rep;
    }

   function destroy(){
      jForms::destroy('sample2',$this->param('id'));
      $rep= $this->getResponse("redirect");
      $rep->action="forms:listform";
      return $rep;
   }

}

?>