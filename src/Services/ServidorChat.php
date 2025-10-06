<?
namespace Glory\Services;

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

// Se asume que el autoload de Composer está disponible en la ruta correcta.
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

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
        echo "Instancia de ServidorChat creada...\n";
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
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $datos = json_decode($msg, true);

        if (isset($datos['accion']) && $datos['accion'] === 'registrar' && isset($datos['idUsuario'])) {
            $idUsuario = filter_var($datos['idUsuario'], FILTER_SANITIZE_NUMBER_INT);
            $this->mapeoUsuarios[$idUsuario] = $from;
            echo "Usuario {$idUsuario} registrado a la conexión {$from->resourceId}\n";
        }
    }

    /**
     * Se llama cuando un cliente se desconecta.
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clientes->detach($conn);

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
     * Envía un mensaje a un usuario específico a través de su conexión.
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


// --- INICIO DEL BLOQUE DE EJECUCIÓN CONDICIONAL ---

/**
 * El siguiente código SOLO se ejecuta cuando el script es llamado desde la
 * línea de comandos (CLI), por ejemplo: `php ServidorChat.php`.
 * Esto previene que el servidor se inicie y bloquee la ejecución cuando
 * WordPress simplemente incluye el archivo para conocer la definición de la clase.
 */
if (php_sapi_name() === 'cli') {
    echo "Ejecutando en modo CLI. Iniciando servidor...\n";

    $loop = Loop::get();
    $servidorChat = new ServidorChat();

    // --- Servidor WebSocket público en el puerto 8080 ---
    $servidorSocket = new SocketServer('0.0.0.0:8080', [], $loop);
    $servidorWs = new WsServer($servidorChat);
    $servidorHttpPrincipal = new RatchetHttpServer($servidorWs);
    $ioServer = new IoServer($servidorHttpPrincipal, $servidorSocket, $loop);

    $servidorSocket->on('error', function (\Exception $e) {
        echo 'Error en el socket principal (8080): ' . $e->getMessage() . "\n";
    });

    // --- Servidor HTTP interno para comunicación con el backend en el puerto 8081 ---
    $servidorHttpInterno = new HttpServer(function (ServerRequestInterface $request) use ($servidorChat) {
        if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== '/send-message') {
            return new Response(404, ['Content-Type' => 'application/json'], json_encode(['estado' => 'endpoint no encontrado']));
        }

        $cuerpo = (string) $request->getBody();
        $datos = json_decode($cuerpo, true);

        if (!isset($datos['idUsuario']) || !isset($datos['payload'])) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['estado' => 'peticion malformada']));
        }

        $idUsuario = $datos['idUsuario'];
        $payloadString = json_encode($datos['payload']);

        $enviado = $servidorChat->enviarMensajeAUsuario($idUsuario, $payloadString);

        if ($enviado) {
            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['estado' => 'mensaje enviado']));
        } else {
            return new Response(404, ['Content-Type' => 'application/json'], json_encode(['estado' => 'usuario no conectado']));
        }
    });

    $socketInterno = new SocketServer('0.0.0.0:8081', [], $loop);
    $servidorHttpInterno->listen($socketInterno);

    $socketInterno->on('error', function (\Exception $e) {
        echo 'Error en el socket interno (8081): ' . $e->getMessage() . "\n";
    });

    echo "Servidor WebSocket escuchando en el puerto 8080\n";
    echo "Servidor HTTP interno escuchando en el puerto 8081\n";
    echo "Iniciando bucle de eventos...\n";

    $loop->run();
}

// --- FIN DEL BLOQUE DE EJECUCIÓN CONDICIONAL ---