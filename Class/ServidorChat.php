<?php

namespace Glory\Class;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer as RatchetHttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

// --- CAMBIO CLAVE ---
// Se ajusta la ruta para subir dos niveles y encontrar la carpeta vendor en la raíz del tema.
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

/**
 * Clase principal del chat que gestiona las conexiones WebSocket.
 */
class ServidorChat implements MessageComponentInterface
{
    protected $clientes;
    protected $mapeoUsuarios; // Almacena [idUsuario => connection]

    public function __construct()
    {
        $this->clientes = new \SplObjectStorage;
        $this->mapeoUsuarios = [];
        echo "Servidor de Chat iniciado con clases actualizadas...\n";
    }

    /**
     * Se llama cuando un nuevo cliente se ha conectado.
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clientes->attach($conn);
        echo "Nueva conexión! ({$conn->resourceId})\n";
    }

    /**
     * Se llama cuando se recibe un mensaje de un cliente.
     * El cliente debe enviar un primer mensaje para registrarse.
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $datos = json_decode($msg, true);

        // El primer mensaje de un cliente debe ser para registrar su ID de usuario.
        if (isset($datos['accion']) && $datos['accion'] === 'registrar' && isset($datos['idUsuario'])) {
            $idUsuario = filter_var($datos['idUsuario'], FILTER_SANITIZE_NUMBER_INT);
            $this->mapeoUsuarios[$idUsuario] = $from;
            echo "Usuario {$idUsuario} registrado a la conexión {$from->resourceId}\n";
            
            // Confirmar registro al cliente
            $from->send(json_encode([
                'tipo' => 'sistema',
                'mensaje' => 'Registro exitoso.'
            ]));
        }
    }

    /**
     * Se llama cuando un cliente se desconecta.
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clientes->detach($conn);

        // Eliminar al usuario del mapeo
        foreach ($this->mapeoUsuarios as $idUsuario => $connection) {
            if ($connection === $conn) {
                unset($this->mapeoUsuarios[$idUsuario]);
                break;
            }
        }
        echo "Conexión {$conn->resourceId} se ha desconectado\n";
    }

    /**
     * Se llama cuando ocurre un error.
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Ocurrió un error: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Envía un mensaje a un usuario específico.
     * Esta función es llamada por el servidor HTTP interno.
     */
    public function enviarMensajeAUsuario($idUsuario, $mensaje)
    {
        if (isset($this->mapeoUsuarios[$idUsuario])) {
            $conexion = $this->mapeoUsuarios[$idUsuario];
            $conexion->send($mensaje);
            return true;
        }
        return false;
    }
}


// --- Inicialización del Servidor ---

$loop = Loop::get();
$servidorChat = new ServidorChat();

// Servidor WebSocket para los clientes (navegadores) en el puerto 8080
$servidorSocket = new SocketServer('0.0.0.0:8080', [], $loop);
$servidorWs = new WsServer($servidorChat);
$servidorHttpPrincipal = new RatchetHttpServer($servidorWs);
$servidorSocket->on('error', function(\Exception $e) {
    echo 'Error en el socket principal: ' . $e->getMessage() . "\n";
});
$ioServer = new IoServer($servidorHttpPrincipal, $servidorSocket, $loop);


// Servidor HTTP interno para comunicación desde el backend de WordPress en el puerto 8081
$servidorHttpInterno = new HttpServer(function (ServerRequestInterface $request) use ($servidorChat) {
    if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== '/send-message') {
        return new Response(404, ['Content-Type' => 'application/json'], json_encode(['estado' => 'no encontrado']));
    }

    $cuerpo = (string) $request->getBody();
    $datos = json_decode($cuerpo, true);

    if (!isset($datos['idUsuario']) || !isset($datos['payload'])) {
        return new Response(400, ['Content-Type' => 'application/json'], json_encode(['estado' => 'datos incompletos']));
    }

    $idUsuario = $datos['idUsuario'];
    $payloadString = json_encode($datos['payload']);
    
    $enviado = $servidorChat->enviarMensajeAUsuario($idUsuario, $payloadString);

    if ($enviado) {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['estado' => 'enviado']));
    } else {
        return new Response(404, ['Content-Type' => 'application/json'], json_encode(['estado' => 'usuario no conectado']));
    }
});
$socketInterno = new SocketServer('0.0.0.0:8081', [], $loop);
$servidorHttpInterno->listen($socketInterno);

$socketInterno->on('error', function(\Exception $e) {
    echo 'Error en el socket interno: ' . $e->getMessage() . "\n";
});


echo "Iniciando bucle de eventos...\n";
$loop->run();
