<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Recuperar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Validator;
use Zend\I18n\Validator as I18nValidator;
use Zend\Db\Adapter\Adapter;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;

use Zend\Db\Sql\Predicate\Expression;

//componentes para enviar un correo
use Zend\Mail;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

//modelos
use Usuario\Model\Entity\Modelopersonal;
use Recuperar\Model\Entity\Modelorecuperar;

class RecuperarController extends AbstractActionController
{
	public $dbAdapter;
    private $auth;
    public $rutas;

    public function __construct() {
        $this->auth = new AuthenticationService();
        $this->auth->setStorage(new SessionStorage('SistemaDeMenciones_17012017120000_hDfDD5f558s52dD_SDjk_DbySD_sdFdgGg4drdaAdsEtgb6yhn'));
    }

    public function getIp(){
        $ip="unknown";
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {  
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
        }
        elseif (isset($_SERVER['HTTP_VIA'])) {  
            $ip = $_SERVER['HTTP_VIA'];  
        }  
        elseif (isset($_SERVER['REMOTE_ADDR'])) {  
            $ip = $_SERVER['REMOTE_ADDR'];  
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }

        return $ip;
    }


    public function recuperarAction()
    {   
    	$layout = $this->layout();
        $layout->setTemplate('layout/layoutLogin.phtml');

        // --------------------------------
        // asignamos las rutas
        $this->rutas=$this->getServiceLocator()->get('Config');

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloRecuperar= new Modelorecuperar($this->dbAdapter);
        $modeloPersonal=new Modelopersonal($this->dbAdapter);


        if($this->getRequest()->isPost()){

        	$usuario=$modeloPersonal->consultarUsuarioCorreo( addslashes(mb_strtolower($_POST["correoUser"],'UTF-8')) );

        	if ($usuario==NULL) {
        		$this->flashMessenger()->setNamespace("msjError")->addMessage("Correo no válido...");
        		return $this->redirect()->toRoute('recuperar');
        	}

            //****************************************************
            //CREAMOS EL CODIGO
        	$hoy=getdate();
        	$codigo=$hoy["hours"].$hoy["mday"].$hoy["minutes"].$usuario["id_user"].$hoy["year"].$hoy["mon"].$hoy["seconds"];
        	$codigo=hash('ripemd160', $codigo);
            //****************************************************

            //****************************************************
            //AGREGAMOS EL CODIGO A LA BD
            $datos                  =array();
            $datos["id_user_rec"]   =$usuario["id_user"];
            $datos["codigo"]        =$codigo;
            $datos["fecha"]         =new Expression('now()');
            $datos["usado"]         ='N';
            $datos["ip"]            =$this->getIp();
        	$modeloRecuperar->add($datos);
            //****************************************************

            //****************************************************
            //PREPARAMOS EL CORREO
        	$cuerpo="Si usted no es el destinatario correcto, omita este mensaje.

                    Señor(a) ".$usuario["nombre_completo"]."
                    La solicitud para recuperar su contraseña fue procesada correctamente.

                    Por favor siga el siguiente enlace para finalizar el proceso de recuperación.
                    
                    URL: ".$this->rutas["url"].$this->url()->fromRoute('codigo', array('cod'=>$codigo))."

                    Procure cambiar su contraseña antes de 24 Horas, tiempo en el cual expirara si sobrepasa ese límite.

                    ATTE El Sistema.
                    Por favor no responda a este correo.";


            $message = new Message();
            $message->addFrom('sistema@noreply.cl', 'Sistema')
                    ->addTo($usuario["correo"], $usuario["nombre_completo"]) //datos destinatario
                    ->setSubject("Recuperar Password"); //asunto
            $message->setBody($cuerpo);
            $message->setEncoding("UTF-8");
            $transport = new Mail\Transport\Sendmail();
            $transport->send($message);
            //****************************************************

        	$this->flashMessenger()->setNamespace("msjExito")->addMessage("Correo enviado. Se envió un correo con los pasos a seguir...");
        	return $this->redirect()->toRoute('login');

        }

        return new ViewModel();

    }


    public function validaCampos($datos, $codigo)
    {

    	if (!isset($datos["correoUser"]) and $datos["correoUser"]=="") {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("El correo de usuario no puede estar vacío...");
            return "ERROR";
        }
        elseif (!filter_var($datos["correoUser"],FILTER_VALIDATE_EMAIL)) {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("El correo de usuario no es valido...");
            return "ERROR";
        }
        elseif (strlen($datos["passUser"])<=3 or strlen($datos["passUser"])>100) {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("La contraseña debe tener más de 3 y menos de 100 caracteres...");
            return "ERROR";
        }
        elseif (strlen($datos["passUserNueva"])<=3 or strlen($datos["passUserNueva"])>100) {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("La contraseña debe tener más de 3 y menos de 100 caracteres...");
            return "ERROR";
        }
        elseif (strlen($datos["correoUser"])>200) {

            $this->flashMessenger()->setNamespace("msjError")->addMessage("El Correo no puede tener más de 200 caracteres...");
            return "ERROR";
        }
        elseif ($datos["passUser"]!=$datos["passUserNueva"]) {
        	$this->flashMessenger()->setNamespace("msjError")->addMessage("Las nuevas contraseñas no coinciden...");
            return "ERROR";
        }

        return "OK";

    }



    public function codigoAction()
    {	
    	$layout = $this->layout();
        $layout->setTemplate('layout/layoutLogin.phtml');
        
        $codigo=$this->params()->fromRoute('cod',null);

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloRecuperar= new Modelorecuperar($this->dbAdapter);
        $modeloPersonal=new Modelopersonal($this->dbAdapter);

        $recuperar=$modeloRecuperar->getPorCode($codigo);
        if ($recuperar==NULL or $recuperar==false) {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("Este código no es válido.");
            return $this->redirect()->toRoute('login');
        }

        if ($recuperar["usado"]!=='N') {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("Este código ya fue utilizado.");
            return $this->redirect()->toRoute('login');
        }

        if($this->getRequest()->isPost()){

            $usuario=$modeloPersonal->get($recuperar["id_user_rec"]);
            $recuperar["diferencia"] = str_replace(array('-', ':'),'', $recuperar["diferencia"]);

        	if ($usuario==NULL) {
        		$this->flashMessenger()->setNamespace("msjError")->addMessage("Usuario inexistente...");
        		return $this->redirect()->toRoute('codigo', array('cod'=>$codigo));
        	}

            if ($_POST["correoUser"]!=$usuario["correo"]) {

                $this->flashMessenger()->setNamespace("msjError")->addMessage("El correo ingresado no coincide con el código...");
                return $this->redirect()->toRoute('codigo', array('cod'=>$codigo));

            }

            if ((int)$recuperar["diferencia"]>240000) {

                $modeloRecuperar->delete($recuperar["id_recuperar"], $recuperar["id_user_rec"], $codigo);
                $this->flashMessenger()->setNamespace("msjError")->addMessage("El tiempo para este código expiro... Solicite uno nuevo si quiere recuperar su contraseña");
                return $this->redirect()->toRoute('codigo', array('cod'=>$codigo));
            }

        
        	if($this->validaCampos($_POST, $codigo)=="ERROR"){
                return $this->redirect()->toRoute('codigo', array('cod'=>$codigo));
            }

            // Actualizamos el uso y la contraseña
            $datos_update           =array();
            $datos_update["usado"]  ='S';
            $modeloRecuperar->update($datos_update, $recuperar["id_recuperar"]);

            //************************************************************************
            //ACTUALIZAMOS LA CONTRASEÑA
            $datos_update               =array();
            $datos_update["password"]   =hash("ripemd160",$_POST["passUser"]);
            $modeloPersonal->update($datos_update, $recuperar["id_user_rec"]);
            //************************************************************************


            $this->flashMessenger()->setNamespace("msjExito")->addMessage("Su contraseña ha sido cambiada con éxito...");
            return $this->redirect()->toRoute('login');

        }

        $msj="";
        if ($recuperar["id_user_rec"]==NULL) {
        	$msj="<b>El código proporcionado es inválido razones:</b>
            <br> -Código no existe
            <br> -El código expiró
            <br> -El código ya fue usado";
        }

    	return new ViewModel(array(
                'ruta'      =>$this->url()->fromRoute('codigo', array('cod'=>$codigo)),
    			'msj'       =>$msj,
    		));

    }



}
