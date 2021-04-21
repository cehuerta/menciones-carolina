<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelocampanas{

    public $dbAdapter;
    public $sql;
    public $table_name='campanas';

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }


    public function add($datos) 
    {
        $insert = $this->sql->insert();
        $insert->into($this->table_name);
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }


    public function all($start=0, $limit=10, $campaign_title=null, $campaign_status=null, $idc=null, $order=null, $like=null) 
    {
        $select = $this->sql->select();

        $exp_fecha          = new Expression('date_format(c.campaign_date_create, "%d.%m.%Y")');
        $exp_hora           = new Expression('time_format(c.campaign_date_create, "%H:%i")');
        $exp_t_menciones    = new Expression('(select count(*) from menciones where idcamp=c.idcamp)');

        $select->columns(array('*', 'campaign_date_create_fecha'=>$exp_fecha, 'campaign_date_create_hora'=>$exp_hora, 't_menciones'=>$exp_t_menciones ), 'c');
        $select->from(array( 'c'=>$this->table_name) );
        $select->join(array( 'cli'=>'clientes'), 'c.idc=cli.idc', array('client_name') );

        if($campaign_title!==null)
            $select->where( new Predicate\Like('c.campaign_title', '%'.$campaign_title.'%' ) );
        if($campaign_status!==null)
            $select->where( array('c.campaign_status'=>$campaign_status) );
        if($idc!==null)
            $select->where( array('c.idc'=>$idc) );

        //LIKE DESDE LA TABLA
        if($like!==null):
            $select->where( new Predicate\Like('c.campaign_title', '%'.$like.'%' ) );
        endif;

        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if($order!==null)
            $select->order($order);
        if($start!==null)
            $select->offset($start);
        if($limit!==null)
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

    public function get($idcamp) 
    {
        $select = $this->sql->select();
        $exp_fecha          = new Expression('date_format(c.campaign_date_create, "%d.%m.%Y")');
        $exp_hora           = new Expression('time_format(c.campaign_date_create, "%H:%i")');
        $exp_t_menciones    = new Expression('(select count(*) from menciones where idcamp=c.idcamp)');


        $select->columns(array('*', 'campaign_date_create_fecha'=>$exp_fecha, 'campaign_date_create_hora'=>$exp_hora, 't_menciones'=>$exp_t_menciones), 'c');
        $select->from(array( 'c'=>$this->table_name) );
        $select->join(array( 'cli'=>'clientes'), 'c.idc=cli.idc', array('client_name') );


        $select->where(array('c.idcamp'=>$idcamp) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    
    public function update($datos, $idcamp)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where(array('idcamp'=>$idcamp) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function delete($idcamp)
    {
        if($idcamp==null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where(array('idcamp'=>$idcamp) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($campaign_slug=null, $campaign_title=null, $idc=null, $campaign_status=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($campaign_slug===null and $campaign_title===null and $idc===null and $campaign_status===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);
        if($campaign_slug!==null)
            $select->where( array('campaign_slug'=>$campaign_slug) );
        if($campaign_title!=null)
            $select->where(array('campaign_title'=>$campaign_title) );
        if($idc!=null)
            $select->where(array('idc'=>$idc) );
        if($campaign_status!=null)
            $select->where(array('campaign_status'=>$campaign_status) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }
}


?>