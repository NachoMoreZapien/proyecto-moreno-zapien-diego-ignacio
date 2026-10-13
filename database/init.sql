CREATE DATABASE IF NOT EXISTS control_incidencias;
USE control_incidencias;

CREATE TABLE IF NOT EXISTS incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('abierta', 'en_progreso', 'resuelta') DEFAULT 'abierta',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO incidencias (titulo, descripcion, estado) VALUES
('Falla en proyector', 'El proyector del aula 3 no enciende.', 'abierta'),
('Problema de red', 'Sin conexión a internet en el laboratorio B.', 'en_progreso'),
('Aire acondicionado', 'Fuga de agua en el equipo de la biblioteca.', 'resuelta');