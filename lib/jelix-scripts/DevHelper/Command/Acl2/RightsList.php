<?php
/**
* @author      Laurent Jouanneau
* @contributor Loic Mathaud
* @copyright   2007-2016 Laurent Jouanneau, 2008 Loic Mathaud
* @link        http://www.jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/

namespace Jelix\DevHelper\Command\Acl2;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class RightsList  extends \Jelix\DevHelper\AbstractCommandForApp {

    protected function configure()
    {
        $this
            ->setName('acl2:list')
            ->setDescription('Show the list of rights')
            ->setHelp('')
        ;
        parent::configure();
    }


    protected function _execute(InputInterface $input, OutputInterface $output)
    {
        $cnx = \jDb::getConnection('jacl2_profile');

        $sql="SELECT r.id_aclgrp, r.id_aclsbj, r.id_aclres, s.label_key as subject
                FROM ".$cnx->prefixTable('jacl2_rights')." r,
                ".$cnx->prefixTable('jacl2_subject')." s
                WHERE r.id_aclgrp = '__anonymous' AND r.id_aclsbj=s.id_aclsbj
                ORDER BY subject, id_aclres ";
        $rs = $cnx->query($sql);

        $table = new Table($output);
        $table->setHeaders(array('Group id', 'Group name', 'Subject', 'Resource'));

        foreach($rs as $rec){
            $table->addRow(array(
                                'Anonymous',
                                $rec->id_aclsbj,
                                $rec->id_aclres
                                ));
        }

        $sql="SELECT r.id_aclgrp, r.id_aclsbj, r.id_aclres, name as grp, s.label_key as subject
                FROM ".$cnx->prefixTable('jacl2_rights')." r,
                ".$cnx->prefixTable('jacl2_group')." g,
                ".$cnx->prefixTable('jacl2_subject')." s
                WHERE r.id_aclgrp = g.id_aclgrp AND r.id_aclsbj=s.id_aclsbj
                ORDER BY grp, subject, id_aclres ";

        $rs = $cnx->query($sql);
        foreach($rs as $rec){
            $table->addRow(array(
                                $rec->id_aclgrp,
                                $rec->grp,
                                $rec->id_aclsbj,
                                $rec->id_aclres
                                ));
        }
        $table->render();
    }
}
