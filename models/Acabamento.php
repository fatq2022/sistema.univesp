<?php
// models/Acabamento.php

class Acabamento {
    private $conn;
    private $table_name = "acabamento"; // Ajuste para o nome exato da tabela se necessário

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_acabamento = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function criar($descricao) {
        $query = "INSERT INTO " . $this->table_name . " (descricao) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$descricao])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function atualizar($id, $descricao) {
        $query = "UPDATE " . $this->table_name . " SET descricao = ? WHERE id_acabamento = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$descricao, $id]);
    }

    public function deletar($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_acabamento = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}