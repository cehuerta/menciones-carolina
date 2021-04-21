<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */


return array(
    'rutas'		=>array(
        'rutaUser'             	=>'/menciones_upload/user', 
        'rutaRaizUser'         	=>$_SERVER["DOCUMENT_ROOT"].'/menciones_upload/user',
    ),
    'api_rudo' 	=>array(
    	'hash_private'	=> '4a5dV44D_sDfSf48f4s1s_FssA_aDffg43f0dfavaA',
    	'urls'			=> array(
    		'send_mention'	=> 'https://api.rudo.video/api/api2/v3/menciones/ingresar',
    	),
    ),
    'url'               =>'http://menciones.carolina.cl/',
    'NoImage'           =>'no-image.jpg',
);

//ruta de la pagina del sistema, usada en la recuperacion
// se envia un correo con esa ruta, y en el controlador de recuperar
// se concatena el resto. (ojo no poner "/" al final)
