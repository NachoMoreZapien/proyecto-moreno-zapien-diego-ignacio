<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);
$route = $request_uri[0];
$method = $_SERVER['REQUEST_METHOD'];

function getDB() {
    $dsn = "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME');
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function getRedis() {
    $redis = new Redis();
    $redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'));
    return $redis;
}

// Función auxiliar para enviar correos a Mailpit por Socket
function enviarCorreo($asunto, $mensaje) {
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = getenv('SMTP_PORT');
    if (!$smtp_host || !$smtp_port) return;

    $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
    if ($socket) {
        $crlf = "\r\n";
        $headers = "From: admin@sistema.local" . $crlf .
                   "To: admin@sistema.local" . $crlf .
                   "Subject: " . $asunto . $crlf .
                   "Content-Type: text/plain; charset=UTF-8" . $crlf .
                   $crlf;

        $body = $mensaje . $crlf;

        fread($socket, 1024);
        fwrite($socket, "HELO localhost" . $crlf);
        fread($socket, 1024);
        fwrite($socket, "MAIL FROM: <admin@sistema.local>" . $crlf);
        fread($socket, 1024);
        fwrite($socket, "RCPT TO: <admin@sistema.local>" . $crlf);
        fread($socket, 1024);
        fwrite($socket, "DATA" . $crlf);
        fread($socket, 1024);
        fwrite($socket, $headers . $body . "." . $crlf);
        fread($socket, 1024);
        fwrite($socket, "QUIT" . $crlf);
        fclose($socket);
    }
}

// 1. Endpoint /health
if ($route === '/health') {
    $status = ["mariadb" => "desconectado", "redis" => "desconectado"];
    try { getDB(); $status["mariadb"] = "conectado"; } catch (Exception $e) {}
    try { getRedis(); $status["redis"] = "conectado"; } catch (Exception $e) {}
    
    $code = ($status["mariadb"] == "conectado" && $status["redis"] == "conectado") ? 200 : 500;
    http_response_code($code); echo json_encode($status); exit;
}

// Rutas de API
if (preg_match('/^\/incidencias(\/([0-9]+))?$/', $route, $matches)) {
    $id = isset($matches[2]) ? $matches[2] : null;
    $pdo = getDB();
    $redis = getRedis();

    // GET: Listar
    if ($method === 'GET' && !$id) {
        $visitas = $redis->incr('contador_consultas');
        $cache = $redis->get('incidencias_lista');
        
        if ($cache) {
            echo json_encode(["origen" => "redis", "visitas" => $visitas, "datos" => json_decode($cache)]);
            exit;
        }

        $stmt = $pdo->query("SELECT * FROM incidencias ORDER BY id DESC");
        $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $redis->setex('incidencias_lista', 30, json_encode($incidencias));
        echo json_encode(["origen" => "mariadb", "visitas" => $visitas, "datos" => $incidencias]);
        exit;
    }

    // POST: Crear (Primer tipo de correo)
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        $stmt = $pdo->prepare("INSERT INTO incidencias (titulo, descripcion, estado) VALUES (?, ?, ?)");
        $stmt->execute([$data->titulo, $data->descripcion, 'abierta']);
        
        $id_insertado = $pdo->lastInsertId();
        $redis->del('incidencias_lista');

        $mensaje_correo = "Se ha registrado una nueva incidencia.\r\n" .
                         "ID: " . $id_insertado . "\r\n" .
                         "Título: " . $data->titulo . "\r\n" .
                         "Descripción: " . $data->descripcion;

        enviarCorreo("Nueva Incidencia Creada (ID: $id_insertado)", $mensaje_correo);

        echo json_encode(["mensaje" => "Incidencia creada y correo enviado"]);
        exit;
    }

    // PUT: Actualizar estado (Segundo tipo de correo)
    if ($method === 'PUT' && $id) {
        $data = json_decode(file_get_contents("php://input"));
        $stmt = $pdo->prepare("UPDATE incidencias SET estado = ? WHERE id = ?");
        $stmt->execute([$data->estado, $id]);
        $redis->del('incidencias_lista');

        $mensaje_correo = "El estado de la incidencia con ID $id ha sido cambiado a: " . strtoupper($data->estado);
        enviarCorreo("Estado de Incidencia Actualizado (ID: $id)", $mensaje_correo);

        echo json_encode(["mensaje" => "Estado actualizado y notificación enviada"]);
        exit;
    }

    // DELETE: Eliminar (Tercer tipo de correo)
    if ($method === 'DELETE' && $id) {
        $stmt = $pdo->prepare("DELETE FROM incidencias WHERE id = ?");
        $stmt->execute([$id]);
        $redis->del('incidencias_lista');

        $mensaje_correo = "Se ha eliminado del sistema la incidencia con ID: " . $id;
        enviarCorreo("Incidencia Eliminada (ID: $id)", $mensaje_correo);

        echo json_encode(["mensaje" => "Incidencia eliminada y notificación enviada"]);
        exit;
    }
}

http_response_code(404); echo json_encode(["error" => "Ruta no encontrada"]);