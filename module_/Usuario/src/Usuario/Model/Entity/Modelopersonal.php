<?php
namespace Usuario\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Resultset\Resultset;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

class Modelopersonal{


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
        $insert->into('usuarios');
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }

    public function contSlug($slug)
    {
        $select = $this->sql->select();
        $select->columns(array( new Expression('count(*) as cont') ), '');

        $select->from('usuarios');
        $select->where('slug="'.$slug.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["cont"];
    }

    public function get($id_user) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from(array( 'u'=>'usuarios') );
        $select->join(array('up'=>'usuarios_profiles'), 'u.tipo_user=up.idprof' );


        $select->where('u.id_user="'.$id_user.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    
    public function getPorEmail($correo) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from('usuarios');

        $select->where('correo="'.$correo.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    

    public function update($datos, $id_user)
    {
        $update = $this->sql->update();
        $update->table('usuarios');
        $update->set($datos);
        $update->where('id_user="'.$id_user.'"');
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }


    public function delete($id_user)
    {
        if($id_user==null){
            return null;
        }

        $select = $this->sql->delete();
        $select->from('usuarios');
        $select->where('id_user='.$id_user);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function varificaCorreo($correo) 
    {
        $select = $this->sql->select();
        $select->columns(array( new Expression('count(*) as contCorreo') ), '');

        $select->from('usuarios');
        $select->where('correo="'.$correo.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["contCorreo"];
    }


    //------------------------------------------------------------
    //usado en el recuperar, para extraer los datos del user a traves del correo. 
    public function consultarUsuarioCorreo($correo) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from('usuarios');

        $select->where('correo="'.$correo.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    



}


?>