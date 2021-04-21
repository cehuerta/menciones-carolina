<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Usuario\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Validator;
use Zend\Filter;
use Zend\I18n\Validator as I18nValidator;
use Zend\Db\Adapter\Adapter;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Expression;



//componentes para enviar un correo
use Zend\Mail;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;

//modelos
use Usuario\Model\Entity\Modelopersonal;
use Usuario\Model\Entity\Modelousuariosperfil;
use Application\Model\Entity\Modelofunctions;

class UsuarioController extends AbstractActionController
{
	public $dbAdapter;
    private $auth;
    public $rutas;


    public function __construct() {
        $this->auth = new AuthenticationService();
        $this->auth->setStorage(new SessionStorage('SistemaDeMenciones_17012017120000_hDfDD5f558s52dD_SDjk_DbySD_sdFdgGg4drdaAdsEtgb6yhn'));
        
    }


	public function validaSesion(){

        $identi=$this->auth->getStorage()->read();

        if($identi!=false && $identi!=null){
            
            // -----------------------------------------------------------------------
            // declaracion de rutas
            $this->rutas=$this->getServiceLocator()->get('Config');

            if( !empty($identi->logo) ) {
                $this->layout()->logo = $this->rutas["rutas"]["rutaUser"]."/".$identi->slug."/".$identi->logo;
            }
            else{
                $this->layout()->logo = $this->getRequest()->getBaseUrl().'/public/img/'.$this->rutas["NoImage"];
            }
            
            $this->layout()->nameUser = $identi->nombre_completo;
            $this->layout()->type = $identi->tipo_user;

        }else{
            return false;
        }

        return $identi;
    }


    public function validaCampos($datos, $action){
        
        if ( !isset($datos["tipo"]) ) {
            if( $action!="MISDATOS" ){
                return "Debe seleccionar un tipo de usuario válido.";
            }
        }


        if( $action!="MISDATOS" ){
            if(!is_numeric($datos["tipo"]) or $datos["tipo"]<=0 ){
                return "Debe seleccionar un tipo de usuario válido.";
            }
        }


        if ( !isset($datos["nombreUsuario"]) or strlen($datos["nombreUsuario"])<=2 or strlen($datos["nombreUsuario"])>200 ) {
            
            return "El campo nombre puede tener entre 3 a 200 caracteres...";
        }
        
        if ( !isset($datos["correoUsuario"]) or $datos["correoUsuario"]=="" or strlen($datos["correoUsuario"])>200) {

            return "El campo correo no puede estar vacío, y debe tener menos de 200 caracteres...";
        }
        
        if( $action!="MISDATOS" and $action!="EDITAR" ){
            if ( !isset($datos["slugUser"]) or $datos["slugUser"]=="" or strlen($datos["slugUser"])>200) {
                return "El campo slug no puede estar vacío, y debe tener menos de 200 caracteres.";
            }
        }
        
        if (!filter_var($datos["correoUsuario"],FILTER_VALIDATE_EMAIL)) {
            return "El correo no es valido...";
        }
        
        if ( !isset($datos["passUsuario"]) or strlen($datos["passUsuario"])<=3 or  strlen($datos["passUsuario"])>100 ) {

            if ($action=="INGRESAR") {
                return "La contraseña debe tener entre 4 a 100 caracteres...";
            }
            elseif ($action=="EDITAR" and $datos["passUsuario"]!="") {
                return "La contraseña debe tener entre 4 a 100 caracteres...";
            }
        }
        

        return "OK";
    }


    public function validaEmail($correo, $correoActual, $action, $id_user)
    {
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        if ($action=="EDITAR") {
            if ($correoActual==strtolower(trim($correo))) {
                return "OK";
            }
        }  

        $resultado=$modeloPersonal->varificaCorreo(strtolower(trim($correo)));
        if ($resultado>0) 
        {
            return "ERROR";
        }

        return "OK";
    }


    // -----------------------------
    // valida contraseñas cuando edita sus propios datos
    public function validaContrasenas($datos, $passActual)
    {
        if ($passActual!=hash("ripemd160",$datos["passUsuario"])) {
            return "Tu contraseña es incorrecta...";
        }
        elseif ($datos["nuevaPassUser1"]!=$datos["nuevaPassUser2"]) {
            return "Las nuevas contraseñas no coinciden...";
        }
        elseif (strlen($datos["nuevaPassUser1"])<=3 or strlen($datos["nuevaPassUser1"])>100) {
            return "La nueva contraseña debe ser tener entre 4 a 100 caracteres...";
        }
        elseif (strlen($datos["nuevaPassUser2"])<=3 or strlen($datos["nuevaPassUser2"])>100) {
            return "La nueva contraseña debe ser tener entre 4 a 100 caracteres...";
        }

        return "OK";
    }


    public function usuariosAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        if($identidad->tipo_user!=1){
            $this->flashMessenger()->setNamespace("msjError")->addMessage("No tienes permitida esta opción.");
            return $this->redirect()->toRoute( 'login' );
        }
        
        //============================================================
        $page=1;
        $length=10;
        $user='';
        $startTable=0;
        if( isset($_GET["user"]) and !empty($_GET["user"]) ){
            $user=urldecode($_GET["user"]);
        }
        if( isset($_GET["length"]) and (int)$_GET["length"]>10 ){
            $length=(int)$_GET["length"];
        }
        if( isset($_GET["page"]) and (int)$_GET["page"]>1 ){
            $page=(int)$_GET["page"];
        }
        $startTable=($page-1)*$length;
        //============================================================

        return new ViewModel(array(
            'user'=>$user,
            'length'=>$length,
            'page'=>$page,
            'startTable'=>$startTable
            ));
    }
    
    

    public function ingresarAction()
    {
    	$identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloUsuarioPerfil=new Modelousuariosperfil($this->dbAdapter);
        $modeloFunctions=new Modelofunctions($this->dbAdapter);

        if($identidad->tipo_user!=1){
            $this->flashMessenger()->setNamespace("msjError")->addMessage("No tienes permitida esta opción.");
            return $this->redirect()->toRoute( 'login' );
        }

        return new ViewModel(array(
            'profiles'  =>$modeloUsuarioPerfil->all(),
            'NoImage'   =>$this->rutas["NoImage"],
        ));   
    }


    public function saveajaxAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }


        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloFunctions=new Modelofunctions($this->dbAdapter);


        if($identidad->tipo_user!=1){
            $json["status"]="error";
            $json["msj"]="No tienes permitida esta opción.";
            return new JsonModel($json);
        }

        $json=array();
        $imgUp=false;
        $imgNombre="";
        $slug="";
        $rutaRaiz=$this->rutas["rutas"]["rutaRaizUser"];


        $result=$this->validaCampos($_POST, "INGRESAR");
        if($result!="OK"){
            $json["status"]="error";
            $json["msj"]=$result;
            return new JsonModel($json);
        }

        if ($this->validaEmail($_POST["correoUsuario"], "correo@actual.cl", "INGRESAR", "0")=="ERROR") {

            $json["status"]="error";
            $json["msj"]="El correo utilizado ya esta en uso, ingrese uno distinto al actual...";
            return new JsonModel($json);
        }

        if (isset($_FILES["image"]["name"]) and !empty($_FILES["image"]["name"]) ) {
            $imgUp=true;
        }
        else{
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una imagen.";
            return new JsonModel($json);
        }


        //SLUG
        $contSlugName=0;
        $cont=1;
        $tmp="";
        do {

            if ($contSlugName==0) {
                $slug=mb_strtolower($modeloFunctions->sanear_string( trim($_POST["slugUser"]) ), 'UTF-8');
                $tmp=$slug;
            }else{
                $slug=$tmp;
                $slug.="-".$cont;
            }
            $contSlugName=$modeloPersonal->contSlug($slug);
            $cont=$cont+1;
        } while ($contSlugName != 0);
        //FIN SLUG

        //============================================================================
        if (!file_exists($rutaRaiz)) {
            mkdir($rutaRaiz,0777);
        }
        $rutaRaiz=$rutaRaiz."/".$slug;
        if (!file_exists($rutaRaiz)) {
            mkdir($rutaRaiz,0777);
        }


        $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);//sacamos la extension
        $fileNombre = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
        $fileTemp=$_FILES["image"]["tmp_name"];
        $extensionesFile = array('png', 'jpg', 'jpeg'); 
        if (in_array($fileExtension,$extensionesFile)==false) {
            $json["status"]="error";
            $json["msj"]="Formato incorrecto, la imagen debe ser \".jpg\", \".jpeg\", o \".png\"...";
            return new JsonModel($json);
        }
        $fileNombre=md5($fileNombre.date("YmdHis"));
        if (strlen($fileNombre)>200) {
            $fileNombre=substr($fileNombre, 0, 200);
        }
        $imgNombre=$fileNombre.date("dHis").".".$fileExtension;

        
        $limit_size         =array();
        $limit_size["min"]  ='1kB';
        $limit_size["max"]  ='10MB';

        $var_file           =array();
        $var_file["file"]   =$_FILES["image"];

        $result_up=$modeloFunctions->uploadFile($var_file, $rutaRaiz, $imgNombre, $limit_size);
        if($result_up["status"]=='error'){
            //retorna en el mismo formato array.
            return new JsonModel($result_up);
        }


        //============================================================================
        //RESIZE DE LA IMAGEN
        $res=$modeloFunctions->resizeImg($rutaRaiz."/".$imgNombre, $rutaRaiz."/".$imgNombre, 200, 200);
        

        //============================================================================
        $datos=array();
        $datos["nombre_completo"]       =addslashes(trim($_POST["nombreUsuario"]));
        $datos["correo"]                =addslashes(strtolower(trim($_POST["correoUsuario"])));
        $datos["password"]              =hash("ripemd160",$_POST["passUsuario"]);
        $datos["tipo_user"]             =(int)addslashes(trim($_POST["tipo"]));
        $datos["slug"]                  =$slug;
        $datos["fecha_registro_user"]   =new Expression('now()');
        $datos["logo"]                  =$imgNombre;
        $datos["status"]                =1;
        $id_user_ing=$modeloPersonal->add( $datos);
        //============================================================================

        $json["status"]="ok";
        $json["msj"]="Usuario ingresado correctamente. Listo para ingresar uno nuevo...";
        return new JsonModel($json);
    }

    public function editarAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }

        $id_user=(int) $this->params()->fromRoute('id',null);
        if ($id_user==NULL) {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("El usuario no existe...");
            return $this->redirect()->toRoute( 'adms' );
        }

        if($identidad->tipo_user!=1){
            $this->flashMessenger()->setNamespace("msjError")->addMessage("No tienes permitida esta opción.");
            return $this->redirect()->toRoute( 'login' );
        }


        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloUsuarioPerfil=new Modelousuariosperfil($this->dbAdapter);

        $usuario=$modeloPersonal->get($id_user);
            
        if ($usuario==NULL) {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("El usuario no existe...");
            return $this->redirect()->toRoute( 'adms' );
        }

        //============================================================
        $queryGET="";
        $page=1;
        $length=10;
        $search='';
        if( isset($_GET["user"]) and !empty($_GET["user"]) ){
            $search=urldecode($_GET["user"]);
        }
        if( isset($_GET["length"]) and (int)$_GET["length"]>10 ){
            $length=(int)$_GET["length"];
        }
        if( isset($_GET["page"]) and (int)$_GET["page"]>1 ){
            $page=(int)$_GET["page"];
        }
        $queryGET='?user='.urlencode($search).'&length='.$length.'&page='.$page;
        //============================================================

        return new ViewModel(array(
            'usuario'               =>$usuario,
            'rutaUser'              =>$this->rutas["rutas"]["rutaUser"],
            'imgNoImage'            =>$this->rutas["NoImage"],
            'profiles'              =>$modeloUsuarioPerfil->all(),
            'queryGET'              =>$queryGET,
        ));   
    }

    public function saveeditarajaxAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloFunctions=new Modelofunctions($this->dbAdapter);


        if($identidad->tipo_user!=1){
            $json["status"]="error";
            $json["msj"]="No tienes permitida esta opción.";
            return new JsonModel($json);
        }

        $json=array();
        $imgUp=false;
        $imgNombre="";
        $slug="";
        $rutaRaiz=$this->rutas["rutas"]["rutaRaizUser"];

        if(!isset($_POST["user"]) or !is_numeric($_POST["user"]) ){
            $json["status"]="error";
            $json["msj"]="El usuario que intenta editar no existe...";
            return new JsonModel($json);
        }

        $user=$modeloPersonal->get( (int)$_POST["user"] );
        if($user==NULL){
            $json["status"]="error";
            $json["msj"]="El usuario que intenta editar no existe...";
            return new JsonModel($json);
        }

        $result=$this->validaCampos($_POST, "EDITAR");
        if($result!="OK"){
            $json["status"]="error";
            $json["msj"]=$result;
            return new JsonModel($json);
        }

        if ($this->validaEmail($_POST["correoUsuario"], $user["correo"], "EDITAR", "0")=="ERROR") {

            $json["status"]="error";
            $json["msj"]="El correo utilizado ya esta en uso, ingrese uno distinto al actual...";
            return new JsonModel($json);
        }

        if (isset($_FILES["image"]["name"])) {
            if ($_FILES["image"]["name"]!="") {
                $imgUp=true;
            }
        }


        $slug=$user["slug"];
        if (!file_exists($rutaRaiz)) {
            mkdir($rutaRaiz,0777);
        }

        $rutaRaiz=$rutaRaiz."/".$slug;
        if (!file_exists($rutaRaiz)) {
            mkdir($rutaRaiz,0777);
        }
        
        if( $imgUp==true){

            $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);//sacamos la extension
            $fileNombre = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
            $fileTemp=$_FILES["image"]["tmp_name"];
            $extensionesFile = array('png', 'jpg', 'jpeg'); 
            if (in_array($fileExtension,$extensionesFile)==false) {
                $json["status"]="error";
                $json["msj"]="Formato incorrecto, la imagen debe ser \".jpg\", \".jpeg\", o \".png\"...";
                return new JsonModel($json);
            }
            $fileNombre=md5($fileNombre.date("YmdHis"));
            if (strlen($fileNombre)>200) {
                $fileNombre=substr($fileNombre, 0, 200);
            }
            $imgNombre=$fileNombre.date("dHis").".".$fileExtension;

            
            $limit_size         =array();
            $limit_size["min"]  ='1kB';
            $limit_size["max"]  ='10MB';

            $var_file           =array();
            $var_file["file"]   =$_FILES["image"];

            $result_up=$modeloFunctions->uploadFile($var_file, $rutaRaiz, $imgNombre, $limit_size);
            if($result_up["status"]=='error'){
                //retorna en el mismo formato array.
                return new JsonModel($result_up);
            }


            // ----------------------------------------------------------------
            //RESIZE DE LA IMAGEN
            $res=$modeloFunctions->resizeImg($rutaRaiz."/".$imgNombre, $rutaRaiz."/".$imgNombre, 200, 200);

            if($user["logo"]!="" ){
                if( file_exists($rutaRaiz."/".$user["logo"]) ){
                    @unlink( $rutaRaiz."/".$user["logo"] );
                }
            }

        }else{
            $imgNombre=$user["logo"];
        }
        

        $pass=$user["password"];
        if( empty($_POST["passUsuario"])){
            $pass=$user["password"];
        }
        else{
            $pass=hash("ripemd160",$_POST["passUsuario"]);
        }


        $datos=array();
        $datos["nombre_completo"]   =addslashes(trim($_POST["nombreUsuario"]));
        $datos["correo"]            =addslashes(strtolower(trim($_POST["correoUsuario"])));
        $datos["password"]          =$pass;
        $datos["tipo_user"]         =(int)addslashes(trim($_POST["tipo"]));
        $datos["logo"]              =$imgNombre;
        $modeloPersonal->update( $datos, $user["id_user"]);

        if($identidad->id_user==$user["id_user"]){
            $identidad->nombre_completo     =$datos["nombre_completo"];
            $identidad->tipo_user           =$datos["tipo_user"];
            $identidad->password            =$datos["password"];
            $identidad->logo                =$datos["logo"];
        }

        $json["status"]="ok";
        $json["msj"]="Usuario editado correctamente...";
        return new JsonModel($json);

    }


    public function statusAction()
    {

        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        if($identidad->tipo_user!=1){
            $json["status"]="error";
            $json["msj"]="No tienes permitida esta opción.";
            return new JsonModel($json);
        }
            
        $rutaSave=$this->rutas["rutas"]["rutaRaizUser"];
        $json=array();
        $id_user=(int)$_POST["id"];
        //--------------------------
        //sacamos los datos del usuario
        $usuario=$modeloPersonal->get($id_user);

        if ($usuario==NULL) {

            $json["status"]="error";
            $json["msj"]="El usuario no existe...";
            return new JsonModel($json);
        }
        if($identidad->id_user==$usuario["id_user"]){
            $json["status"]="error";
            $json["msj"]="No es posible deshabilitarse a si mismo...";
            return new JsonModel($json);
        }

        $status=0;
        $json["msj"]="Usuario deshabilitado correctamente...";
        if($usuario["status"]==0){//si esta deshabilitado
            $status=1;//habilitamos, si no entra significa que esta actualmente habilitado, por ende se deshabilita
            $json["msj"]="Usuario habilitado correctamente...";

        }

        $datos_update=array();
        $datos_update["status"]=$status;
        $modeloPersonal->update($datos_update, $usuario["id_user"]);
        
        $json["status"]="ok";

        return new JsonModel($json);

    }


    public function eliminarAction()
    {

        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }
        
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloFunctions=new Modelofunctions($this->dbAdapter);
            

        if( $identidad->tipo_user!=1 ){
            $json["status"]="error";
            $json["msj"]="No tienes permitida esta opción.";
            return new JsonModel($json);
        }

        $rutaSave=$this->rutas["rutas"]["rutaRaizUser"];
        $json=array();
        $id_user=(int)$_POST["id"];

        //--------------------------
        //sacamos los datos del usuario
        $usuario=$modeloPersonal->get($id_user);

        if ($usuario==NULL) {
            $json["status"]="error";
            $json["msj"]="El usuario no existe...";
            return new JsonModel($json);
        }
        if($identidad->id_user==$usuario["id_user"]){
            $json["status"]="error";
            $json["msj"]="No es posible eliminarse a si mismo.";
            return new JsonModel($json);
        }


        //eliminamos al usuario
        $modeloPersonal->delete( $usuario["id_user"] );
        $slugU="nomeelimines12112212854545";
        if( !empty($usuario["slug"]) ){
            $slugU=$usuario["slug"];
        }
        
        $rutaSave=$rutaSave."/".$slugU;
        $modeloFunctions->eliminarDirectorio($rutaSave);
        
        $json["status"]="ok";
        $json["msj"]="Usuario eliminado correctamente.";
        return new JsonModel($json);

    }

    public function viewsendmailAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        

        $json=array();

        if(!isset($_POST["asunto"]) or !isset($_POST["contenido"]) or !isset($_POST["user"]) or empty($_POST["asunto"]) or empty($_POST["contenido"]) or empty($_POST["user"])  ){
            $json["status"]="error";
            $json["msj"]="Debe ingresar el asunto y/o contenido del correo...";
            return new JsonModel($json);
        }

        $user=$modeloPersonal->get( (int)$_POST["user"] );
        if($user==NULL){
            $json["status"]="error";
            $json["msj"]="El usuario al que intenta contactar no existe...";
        }

        $res=$this->sendMail($identidad->correo, $identidad->nombre_completo." (sistema)", $_POST["contenido"], $user["correo"], $user["nombre_completo"], $_POST["asunto"]);
        if($res==true){
            $json["status"]="ok";
            $json["msj"]="Correo enviado...";
        }else{
            $json["status"]="error";
            $json["msj"]="Ocurrió un problema mientras se enviaba el correo, por favor, intentelo mas tarde...";
        }
        return new JsonModel($json);

    }
    
    public function editarmisdatosAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        $usuario=$modeloPersonal->get($identidad->id_user);
            
        if ($usuario==NULL) {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("El Usuario no existe...");
            return $this->redirect()->toUrl($this->getRequest()->getBaseUrl().'/admins'); 
        }

        return new ViewModel(array(
            'usuario'=>$usuario,
            'rutaUser'=>$this->rutas["rutas"]["rutaUser"],
            'imgNoImage'=>$this->rutas["NoImage"],
        ));   
    }

    public function editarmisdatosajaxAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPersonal=new Modelopersonal($this->dbAdapter);
        $modeloFunctions=new Modelofunctions($this->dbAdapter);


        $json=array();
        $imgUp=false;
        $imgNombre="";
        $rutaRaiz=$this->rutas["rutas"]["rutaRaizUser"];

        $user=$modeloPersonal->get( (int)$_POST["user"] );
        if($user==NULL){
            $json["status"]="error";
            $json["msj"]="El usuario que intenta editar no existe...";
            return new JsonModel($json);
        }

        $result=$this->validaCampos($_POST, "MISDATOS");
        if($result!="OK"){
            $json["status"]="error";
            $json["msj"]=$result;
            return new JsonModel($json);
        }

        if ($this->validaEmail($_POST["correoUsuario"], $user["correo"], "EDITAR", 0)=="ERROR") {

            $json["status"]="error";
            $json["msj"]="El correo utilizado ya esta en uso, ingrese uno distinto al actual...";
            return new JsonModel($json);
        }

        if ($_POST["cambiarContrasena"]=="SI") {
            $res_val_pass=$this->validaContrasenas($_POST, $user["password"]);
            if ($res_val_pass!="OK") {

                $json["status"]="error";
                $json["msj"]=$res_val_pass;
                return new JsonModel($json);
            }
            $_POST["passUsuario"]=$_POST["nuevaPassUser1"];
            $identidad->password=hash("ripemd160", $_POST["passUsuario"]);
            $_POST["passUsuario"]=$identidad->password;
        }
        else{
            $_POST["passUsuario"]=$user["password"];
        }


        if (isset($_FILES["image"]["name"])) {

            if ($_FILES["image"]["name"]!="") {
                $imgUp=true;
            }
        }

        if( $imgUp==true){
            $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);//sacamos la extension
            $fileNombre = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
            $fileTemp=$_FILES["image"]["tmp_name"];
            $extensionesFile = array('png', 'jpg', 'jpeg'); 
            if (in_array($fileExtension,$extensionesFile)==false) {
                $json["status"]="error";
                $json["msj"]="Formato incorrecto, la imagen debe ser \".jpg\", \".jpeg\", o \".png\"...";
                return new JsonModel($json);
            }
            $fileNombre=md5($fileNombre.date("YmdHis"));
            if (strlen($fileNombre)>200) {
                $fileNombre=substr($fileNombre, 0, 200);
            }
            $imgNombre=$fileNombre.date("dHis").".".$fileExtension;


            if (!file_exists($rutaRaiz)) {
                mkdir($rutaRaiz,0777);
            }

            $rutaRaiz=$rutaRaiz."/".$user["slug"];
            if (!file_exists($rutaRaiz)) {
                mkdir($rutaRaiz,0777);
            }

            
            $limit_size         =array();
            $limit_size["min"]  ='1kB';
            $limit_size["max"]  ='10MB';

            $var_file           =array();
            $var_file["file"]   =$_FILES["image"];

            $result_up=$modeloFunctions->uploadFile($var_file, $rutaRaiz, $imgNombre, $limit_size);
            if($result_up["status"]=='error'){
                //retorna en el mismo formato array.
                return new JsonModel($result_up);
            }

            

            // ----------------------------------------------------------------
            //RESIZE DE LA IMAGEN
            $res=$modeloFunctions->resizeImg($rutaRaiz."/".$imgNombre, $rutaRaiz."/".$imgNombre, 200, 200);

            if($user["logo"]!="" ){
                @unlink( $rutaRaiz."/".$user["logo"] );
            }

        }else{
            $imgNombre=$user["logo"];
        }


        $slug=$user["slug"];
        $datos=array();
        $datos["nombre_completo"]=addslashes(trim($_POST["nombreUsuario"]));
        $datos["correo"]=addslashes(strtolower(trim($_POST["correoUsuario"])));
        $datos["password"]=$_POST["passUsuario"]; //ya fue hascheado arriba
        $datos["logo"]=$imgNombre;

        $id_user_ing=$modeloPersonal->update( $datos, $user["id_user"]);

        //=======================================================
        $identidad->nombre_completo=stripslashes($datos["nombre_completo"]);
        $identidad->correo=stripslashes($datos["correo"]);
        $identidad->logo=$datos["logo"];
        //=======================================================
        
        $json["status"]="ok";
        $json["msj"]="Datos actualizados correctamente.";
        return new JsonModel($json);

    }


    public function sendMail($from, $fromName, $body, $to, $toName, $subject)
    {
        //NECESARIOS PARA EL HTML
        $bodyPart = new \Zend\Mime\Message();
        $bodyMessage = new \Zend\Mime\Part($body);
        $bodyMessage->type = 'text/html';
        $bodyPart->setParts(array($bodyMessage));
        //FIN NECESARIOS PARA EL HTML


        $message = new Message();
        $message->addFrom($from, $fromName)
                    ->addTo($to, $toName) //datos destinatario
                    ->setSubject($subject); //asunto
        // $message->setBody($body);
        $message->setBody($bodyPart);
        $message->setEncoding("UTF-8");
        $transport = new Mail\Transport\Sendmail();

        try {
            $transport->send($message);
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }

}
