<?php
namespace Recuperar\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Resultset\Resultset;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

class Modelorecuperar{


    public $dbAdapter;
    public $sql;

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }


    public function add($datos) 
    {
        $insert = $this->sql->insert();
        $insert->into('recuperar_pass');
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();
        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }


    public function getPorCode($codigo) 
    {
        $select = $this->sql->select();

        $exp_diff=new Expression('TIMEDIFF( fecha, NOW() )');
        $select->columns(array('*', 'diferencia'=>$exp_diff), '');
        $select->from(array( 'rp'=>'recuperar_pass') );

        $select->where( array('rp.codigo'=>$codigo) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }

    public function update($datos, $id_recuperar)
    {
        $update = $this->sql->update();
        $update->table('recuperar_pass');
        $update->set($datos);
        $update->where('id_recuperar="'.$id_recuperar.'"');
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }


    public function delete($id_recuperar=null, $id_user_rec=null, $codigo=null)
    {
        if($id_recuperar===null and $id_user_rec===null and $codigo===null){
            return null;
        }

        $select = $this->sql->delete();
        $select->from('recuperar_pass');

        if($id_recuperar!==null)
          $select->where(array('id_recuperar'=>$id_recuperar) );

        if($id_user_rec!==null)
          $select->where(array('id_user_rec'=>$id_user_rec) );
        
        if($codigo!==null)
          $select->where(array('codigo'=>$codigo) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }

}


?>