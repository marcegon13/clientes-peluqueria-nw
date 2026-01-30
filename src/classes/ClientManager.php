<?php
// src/classes/ClientManager.php

class ClientManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Standardizer for Redirects
    private function redirect($params) {
        $queryString = http_build_query($params);
        header("Location: index.php?$queryString");
        exit;
    }

    public function createClient($data) {
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');

        if (empty($nombre) || empty($apellido)) {
            $this->redirect(['error' => 'Nombre y Apellido son obligatorios']);
        }

        $stmt = $this->conn->prepare("INSERT INTO clientes (nombre, apellido) VALUES (?, ?)");
        if (!$stmt) {
             throw new Exception("Error BD: " . $this->conn->error);
        }
        $stmt->bind_param("ss", $nombre, $apellido);

        if ($stmt->execute()) {
            $new_client_id = $stmt->insert_id;
            
            // Optional: Add first work
            $new_trabajo = trim($data['new_trabajo'] ?? '');
            $new_fecha = $data['new_fecha'] ?? '';
            $new_estilista = trim($data['new_estilista'] ?? '');

            if (!empty($new_trabajo) && !empty($new_fecha)) {
                $this->addWork($new_client_id, $new_trabajo, $new_estilista, $new_fecha, false);
            }

            // Success Redirect
            $this->redirect([
                'success' => 1, 
                'msg' => 'Cliente creado exitosamente',
                'search_term' => "$nombre $apellido"
            ]);
        } else {
            $this->redirect(['error' => 'Error al guardar cliente']);
        }
    }

    public function addWork($clientId, $trabajo, $estilista, $fecha, $redirect = true) {
        if ($clientId <= 0 || empty($trabajo) || empty($fecha)) {
             if ($redirect) $this->redirect(['error' => 'Datos inválidos para agregar trabajo']);
             return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO trabajos (cliente_id, trabajo_realizado, estilista, fecha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $clientId, $trabajo, $estilista, $fecha);
        $result = $stmt->execute();

        if ($redirect) {
            if ($result) {
                // Fetch name for persistent search
                $client = $this->getClientName($clientId);
                $this->redirect([
                    'success' => 1,
                    'msg' => 'Trabajo agregado correctamente',
                    'search_term' => $client ? $client['nombre'] . ' ' . $client['apellido'] : ''
                ]);
            } else {
                $this->redirect(['error' => 'Error al guardar trabajo']);
            }
        }
        return $result;
    }

    public function editLastWork($clientId, $trabajo, $estilista, $fecha) {
        // Logic: Update the MOST RECENT work for this client. 
        // Note: This logic was in the original file. It updates based on date desc limit 1.
        // Warning: If multiple works exist, this blindly updates the "latest" one.
        
        $stmt = $this->conn->prepare("
            UPDATE trabajos 
            SET trabajo_realizado = ?, estilista = ?, fecha = ?
            WHERE cliente_id = ?
            ORDER BY fecha DESC LIMIT 1
        ");
        $stmt->bind_param("sssi", $trabajo, $estilista, $fecha, $clientId);
        
        if ($stmt->execute()) {
             $client = $this->getClientName($clientId);
             $this->redirect([
                'success' => 1,
                'msg' => 'Trabajo editado correctamente',
                'search_term' => $client ? $client['nombre'] . ' ' . $client['apellido'] : ''
            ]);
        } else {
            $this->redirect(['error' => 'Error al editar trabajo']);
        }
    }

    public function deleteClient($clientId) {
        if ($clientId <= 0) $this->redirect(['error' => 'ID inválido']);

        // 1. Delete works
        $stmt_works = $this->conn->prepare("DELETE FROM trabajos WHERE cliente_id = ?");
        $stmt_works->bind_param("i", $clientId);
        $stmt_works->execute();

        // 2. Delete client
        $stmt_client = $this->conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt_client->bind_param("i", $clientId);

        if ($stmt_client->execute()) {
            $this->redirect(['success' => 1, 'msg' => 'Cliente eliminado correctamente']);
        } else {
            $this->redirect(['error' => 'Error al eliminar cliente']);
        }
    }

    private function getClientName($id) {
        $stmt = $this->conn->prepare("SELECT nombre, apellido FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
