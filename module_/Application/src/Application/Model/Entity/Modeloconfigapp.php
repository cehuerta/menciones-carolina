<?php
namespace Application\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modeloconfigapp{

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
        $insert->into('configs_app');
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }


    public function all($configs_app_title=null, $start=null, $limit=null) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from(array( 'ca'=>'configs_app') );

        if($configs_app_title!==null)
            $select->where( new Predicate\Like('ca.configs_app_title', '%'.$configs_app_title.'%' ) );

        if( $start!==null )
            $select->offset($start);
        if( $limit!==null )
            $select->limit($limit);
 
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();

        $resultSet = new ResultSet;
        $result=$resultSet->initialize($result);
        return $result->toArray();
    }

    public function found() 
    {
        //UTILIZADO PARA OBTENER EL TOTAL DE RESULTADOS SEGUN LA OBTENCION DE RESULTADOS DEL METODO "ALL"
        $select = $this->sql->select();
        $select->columns(array( new Expression('FOUND_ROWS() as total') ), '');
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function get($idconf_app=null, $configs_app_type=null) 
    {
        $select = $this->sql->select();
        $exp_fecha=new Expression('date_format(configs_app_date, "%d.%m.%Y")');
        $exp_hora=new Expression('time_format(configs_app_date, "%H:%i")');

        $select->columns(array('*', 'configs_app_date_fecha'=>$exp_fecha, 'configs_app_date_hora'=>$exp_hora), '');
        $select->from(array( 'ca'=>'configs_app') );

        if($idconf_app!==null)
            $select->where( array('ca.idconf_app'=>$idconf_app) );
        if($configs_app_type!==null)
            $select->where( array('ca.configs_app_type'=>$configs_app_type) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    



    public function update($datos, $idconf_app)
    {
        $update = $this->sql->update();
        $update->table('configs_app');
        $update->set($datos);
        $update->where( array('idconf_app'=>$idconf_app) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }



    public function delete($idconf_app=null)
    {
        if($idconf_app===null){
            return null;
        }

        $select = $this->sql->delete();
        $select->from('configs_app');
        if($idconf_app!==null)
            $select->where( array('idconf_app'=>$idconf_app) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($configs_app_type=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if( $configs_app_type===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from('configs_app');
        if($configs_app_type!==null)
            $select->where( array('configs_app_type'=>$configs_app_type) );

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }

}


?>