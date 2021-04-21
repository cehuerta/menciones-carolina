<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelomenciones{

    public $dbAdapter;
    public $sql;
    public $table_name='menciones';

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


    public function all($start=0, $limit=20, $idcamp=null, $mention_description=null, $mention_status=null, $mention_date_start=null, $mention_date_end=null, $order=null, $like=null, $idr=null ) 
    {
        $select = $this->sql->select();

        $exp_fecha_inicio       = new Expression('date_format(m.mention_date_start, "%d.%m.%Y")');
        $exp_fecha_fin          = new Expression('date_format(m.mention_date_end, "%d.%m.%Y")');
        $exp_fecha_create_fecha = new Expression('date_format(m.mention_date_create, "%d.%m.%Y")');
        $exp_hora_create_hora   = new Expression('time_format(m.mention_date_create, "%H:%i")');
        $exp_t_horarios         = new Expression('(select count(*) from horarios where idm=m.idm)');
        $exp_concat_group       = new Expression('(select GROUP_CONCAT(DISTINCT date_format(schedule_hour, "%H:%i") ORDER BY schedule_hour ASC SEPARATOR ", ") from horarios where idm=m.idm)');

        $select->columns(array('*', 'hora_string'=> $exp_concat_group, 't_horarios'=>$exp_t_horarios, 'mention_date_start_f'=>$exp_fecha_inicio, 'mention_date_end_f'=>$exp_fecha_fin, 'mention_date_create_fecha'=>$exp_fecha_create_fecha, 'mention_date_create_hora'=>$exp_hora_create_hora ), 'm');
        $select->from(array( 'm'=>$this->table_name) );
        $select->join(array('camp'=>'campanas'), 'm.idcamp=camp.idcamp', array('campaign_title'));

        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if($idcamp!==null)
            $select->where( array('m.idcamp'=>$idcamp) );

        if($mention_description!==null)
            $select->where( new Predicate\Like('m.mention_description', '%'.$mention_description.'%' ) );

        if($mention_status!==null)
            $select->where( array('m.mention_status'=>$mention_status) );

        if($mention_date_start!==null)
            $select->where( array('m.mention_date_start'=>$mention_date_start) );

        if($mention_date_end!==null)
            $select->where( array('m.mention_date_end'=>$mention_date_end) );

        if($idr!==null)
            $select->where( array('camp.idr'=>$idr) );

        //LIKE DESDE LA TABLA
        if($like!==null):
            $select->where( new Predicate\Like('m.mention_description', '%'.$like.'%' ) );
        endif;

        if($start!==null)
            $select->offset($start);
        if($limit!==null)
            $select->limit($limit);

        if($order!==null)
            $select->order($order);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        // var_dump($statement); die;
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

    public function get($idm) 
    {
        $select = $this->sql->select();

        $exp_fecha_inicio       =new Expression('date_format(m.mention_date_start, "%d.%m.%Y")');
        $exp_fecha_fin          =new Expression('date_format(m.mention_date_end, "%d.%m.%Y")');
        $exp_fecha_create_fecha =new Expression('date_format(m.mention_date_create, "%d.%m.%Y")');
        $exp_hora_create_hora   =new Expression('time_format(m.mention_date_create, "%H:%i")');

        $select->columns(array('*', 'mention_date_start_f'=>$exp_fecha_inicio, 'mention_date_end_f'=>$exp_fecha_fin, 'mention_date_create_fecha'=>$exp_fecha_create_fecha, 'mention_date_create_hora'=>$exp_hora_create_hora), '');
        $select->from(array( 'm'=>$this->table_name) );

        $select->where(array('m.idm'=>$idm) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }

    public function update($datos, $idm)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where(array('idm'=>$idm) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function delete($idm)
    {
        if($idm==null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where(array('idm'=>$idm) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($idcamp=null, $mention_description=null, $mention_status=null, $mention_date_start=null, $mention_date_end=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($idcamp===null and $mention_description===null and $mention_status===null and $mention_date_start===null and $mention_date_end===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);

        if($idcamp!==null)
            $select->where( array('idcamp'=>$idcamp) );
        if($mention_description!=null)
            $select->where(array('mention_description'=>$mention_description) );
        if($mention_status!=null)
            $select->where(array('mention_status'=>$mention_status) );
        if($mention_date_start!==null)
            $select->where( array('mention_date_start'=>$mention_date_start) );
        if($mention_date_end!==null)
            $select->where( array('mention_date_end'=>$mention_date_end) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }



    public function all_new( $query=array() ) 
    {
        $select           = $this->sql->select();
        $exp_fecha_start  = new Expression('date_format(m.mention_date_start, "%d.%m.%Y")');
        $exp_fecha_end    = new Expression('date_format(m.mention_date_end, "%d.%m.%Y")');
        $exp_fecha        = new Expression('date_format(m.mention_date_create, "%d.%m.%Y")');
        $exp_hora         = new Expression('time_format(m.mention_date_create, "%H:%i")');
        $exp_horario_hora = new Expression('time_format(h.schedule_hour, "%H:%i")');
        $exp_t_horarios   = new Expression('(select count(*) from horarios where idm=m.idm)');
        $fecha_dia        = ( isset($query['fecha_dia']) && $query['fecha_dia']!=null )? $query['fecha_dia'] : null;
        
        $select->columns(array(
            '*', 
            'mention_date_start_f'      => $exp_fecha_start, 
            'mention_date_end_f'        => $exp_fecha_end, 
            'mention_date_create_fecha' => $exp_fecha, 
            'mention_date_create_hora'  => $exp_hora, 
            'schedule_hour_f'           => $exp_horario_hora, 
            't_horarios'                => $exp_t_horarios, 
        ), 'm');
        $select->from(array( 'm'=>$this->table_name) );
        $select->join(array( 'c'=>'campanas'), 'm.idcamp=c.idcamp', array('campaign_title', 'idcamp') );
        $select->join(array( 'h'=>'horarios'), 'm.idm=h.idm', array('schedule_hour', 'idsched') );
        $select->join(array( 'cli'=>'clientes'), 'c.idc=cli.idc', array('client_name') );
        $select->join(array( 'mv'=>'menciones_vistas'), new Expression("mv.idm=m.idm and mv.idsched=h.idsched and mv.mention_view_date=?",$fecha_dia), array('idmv'), $select::JOIN_LEFT );


        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );
        
        if(isset($query['idr']) )
            $select->where(array('c.idr'=>$query['idr']));

        if(isset($query['search']) )
            $select->where('( (c.campaign_title LIKE "%'.$query['search'].'%") OR (m.mention_description LIKE "%'.$query['search'].'%") )');

        if(isset($query['mention_days']) )
            $select->where( 'm.mention_days LIKE "%'.$query['mention_days'].'%"');

        if( isset($query['mention_status']) )
            $select->where(array('m.mention_status'=>$query['mention_status']));

        if(isset($query['mention_date_start']) )
            $select->where(array('m.mention_date_start'=>$query['mention_date_start']));

        if( isset($query['mention_date_end']) )
            $select->where(array('m.mention_date_end'=>$query['mention_date_end']));

        if( !empty($fecha_dia) )
            $select->where("m.mention_date_start<='".$fecha_dia."' AND m.mention_date_end>='".$fecha_dia."'");

        if(isset($query['notification_hora']) && $query['notification_hora']!==null)
            $select->where('h.schedule_hour<="'.$query['notification_hora'].'"');

        if(isset($query['limit']) && $query['limit']!==null)
            $select->limit( (int)$query['limit'] );

        if(isset($query['start']) && $query['start']!==null)
            $select->offset( (int)$query['start'] );

        if(isset($query['order']) && $query['order']!==null)
            $select->order($query['order']);
        else
            $select->order('m.idm ASC');
        
        $statement = $this->sql->prepareStatementForSqlObject($select);
        // echo "<pre>";
        // print_r($statement);
        // echo "</pre>";
        $result    = $statement->execute();
        
        $resultSet = new ResultSet;
        $result    = $resultSet->initialize($result);
        return $result->toArray();
    }
}


?>