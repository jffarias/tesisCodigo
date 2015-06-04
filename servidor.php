<?php

/*Codigo hecho basandose en la pagina principal*/
/*http://php.net/manual/es/index.php*/
/*http://php.net/manual/es/sockets.examples.php*/
/*Jesus Flor Farias @Iesous_Flor*/


//Establece cuáles errores de PHP son notificados
//int error_reporting ([ int $level ] )
error_reporting(E_ALL);

/* Permitir al script esperar para conexiones. */
set_time_limit(0);

/* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo
* mientras llega. */
ob_implicit_flush();

// direccion y puerto de escucha de este server.
//$address = '192.168.1.81';
$address = 'mi direccion ip';
$port = 10000;

// resource socket_create ( int $domain , int $type , int $protocol )
if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
}

// Vincula un nombre a un socket
// bool socket_bind ( resource $socket , string $address [, int $port = 0 ] )
if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
}

//  Escucha una conexión sobre un socket
// bool socket_listen ( resource $socket [, int $backlog = 0 ] )  // en cola un max de $backlog conexiones
if (socket_listen($sock, 5) === false) {
    echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
}

//clients array, inicialmente vacio
$clients = array();

do {
    $read = array();  // un arreglo vacio 
    $read[] = $sock;    // agrega el socket de escucha al inicio del arreglo
   
    $read = array_merge($read,$clients);  // combina arrays 
   
    // Set up a blocking call to socket_select
    // ejecuta un select() sobre las mattrices de socket dadas en un tiempo especificado
    //if(socket_select($read, $write = NULL, $except = NULL, $tv_sec = 5) < 1)
    $write=NULL;
    $except = NULL;
    // $write es la matriz (vacia) de sockets son observados para ver si una escritura no bloqueara
    // $ecept es la matriz de sockets observados para excepciones
    // timeout de 0 para pooling
    // si hay algo intersante la matriz $read cambiará
    if(socket_select($read, $write, $except, 0) < 1)	
    {
        //    SocketServer::debug("Problem blocking socket_select?");
        continue;   // no hay nada interesante
    }
   
    // Handle new Connections
    if (in_array($sock, $read)) {       // si $sock esta en el arreglo $read
       // Acepta una conexión de un socket, regresa un socket en caso de exito
        if (($msgsock = socket_accept($sock)) === false) {
            echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
            break;
        }
        $clients[] = $msgsock;  // el socket nuevo se agrega a la lista de clientes
        $key = array_keys($clients, $msgsock);   // y se obtiene su indice en el arreglo
        /* Enviar instrucciones. */
        $msg = "\nBienvenido al Servidor De Prueba de PHP. \r\n" .
        "Usted es el cliente numero: {$key[0]}\r\n" .
        "Para salir, escriba 'quit'. Para cerrar el servidor escriba 'shutdown'.\r\n";
        
        //Escribir en un socket
        //int socket_write ( resource $socket , string $buffer [, int $length = 0 ] )
        socket_write($msgsock, $msg, strlen($msg));
       	
       	socket_getpeername($msgsock, $ip, $puerto);  // obtiene ip y puerto del cliente
        echo "Nueva conexion al servidor: $ip:$puerto\n";

        //$from = NULL;
        //$port = 0;
        //socket_recvfrom($sock, $buf, 12, 0, $from, $port);

        //echo "Se recibió $buf desde la dirección remota {$from} y el puerto remoto $port" . PHP_EOL;
    }
   
    // Handle Input
    $all_read_messages=array();
    foreach ($clients as $key => $client) { // obtenemos el valor y la clave 
        //Comprueba si un valor existe en un array
        //bool in_array ( mixed $needle , array $haystack [, bool $strict = FALSE ] )      
        if (in_array($client, $read)) { 
            //socket_read — Lee un máximo de longitud de bytes desde un socket
            //string socket_read ( resource $socket , int $length [, int $type = PHP_BINARY_READ ] )
            if (false === ($buf = socket_read($client, 2048, PHP_BINARY_READ))) {
                echo "socket_read() falló: razón: " . socket_strerror(socket_last_error($client)) . "\n";
                break 2;
            }
            //trim — Elimina espacio en blanco (u otro tipo de caracteres) del inicio y el final de la cadena
            if (!$buf = trim($buf)) {
                continue;
            }
            if ($buf == 'quit') {
                //unset — Destruye una variable especificada
                //void unset ( mixed $var [, mixed $... ] )
                unset($clients[$key]);
                socket_close($client);
                break;
            }
            if ($buf == 'shutdown') {
                socket_close($client);
                break 2;
            }
    	    $all_read_messages[]="$key: $buf\r\n";
            //$talkback = "\r\nCliente {$key}: $buf\r\n";
            //socket_write($client, $talkback, strlen($talkback));
            echo "Cliente {$key}: $buf\n";
        }
    }
    //enviamos la cadena recibida a todos los clientes.
    foreach($clients as $key => $cliente){
        foreach($all_read_messages as $keydummy => $mensaje){
		  socket_write($cliente, $mensaje, strlen($mensaje));
	   }
    }
} while (true);

socket_close($sock);
?>
