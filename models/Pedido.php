<?php
class Pedido {
    private $conn;
    private $table = "pedido";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = " SELECT p.*, s.nome AS shaper, c.descricao AS composicao, v.descricao AS variacao, a.descricao AS acabamento, k.descricao AS configuracaoquilha, q.descricao AS sistemaquilha, GROUP_CONCAT(DISTINCT t.descricao SEPARATOR ', ') AS tecidos, GROUP_CONCAT(DISTINCT co.descricao SEPARATOR ', ') AS cores, GROUP_CONCAT(DISTINCT co.id_cor) AS cores_ids, GROUP_CONCAT(DISTINCT st.id_status ORDER BY ps.data ASC) AS status_ids, GROUP_CONCAT(DISTINCT st.descricao ORDER BY ps.data ASC SEPARATOR ', ') AS status, GROUP_CONCAT(DISTINCT ps.data ORDER BY ps.data ASC SEPARATOR ', ') AS datas_status, GROUP_CONCAT(DISTINCT ps.observacao ORDER BY ps.observacao ASC SEPARATOR ', ') AS observacoes_status, GROUP_CONCAT(DISTINCT u.nome ORDER BY ps.data ASC SEPARATOR ', ') AS usuarios_status FROM {$this->table} p INNER JOIN shaper s ON p.id_shaper = s.id_shaper INNER JOIN composicao c ON p.id_composicao = c.id_composicao INNER JOIN variacao v ON p.id_variacao = v.id_variacao INNER JOIN acabamento a ON p.id_acabamento = a.id_acabamento LEFT JOIN pedido_tecido pt ON p.id_pedido = pt.id_pedido LEFT JOIN tecido t ON pt.id_tecido = t.id_tecido INNER JOIN configuracaoquilha k ON p.id_configuracaoquilha = k.id_configuracaoquilha INNER JOIN sistemaquilha q ON p.id_sistemaquilha = q.id_sistemaquilha LEFT JOIN pedido_cor pc ON p.id_pedido = pc.id_pedido LEFT JOIN cor co ON pc.id_cor = co.id_cor LEFT JOIN pedido_status ps ON p.id_pedido = ps.id_pedido LEFT JOIN status st ON ps.id_status = st.id_status LEFT JOIN usuario u ON ps.id_usuario = u.id_usuario GROUP BY p.id_pedido ORDER BY p.id_pedido DESC ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($id) {
        $query = " SELECT p.*, s.nome AS shaper, c.descricao AS composicao, v.descricao AS variacao, a.descricao AS acabamento, k.descricao AS configuracaoquilha, q.descricao AS sistemaquilha, GROUP_CONCAT(DISTINCT t.id_tecido) AS tecidos_ids, GROUP_CONCAT(DISTINCT t.descricao SEPARATOR ', ') AS tecidos, GROUP_CONCAT(DISTINCT co.id_cor) AS cores_ids, GROUP_CONCAT(DISTINCT co.descricao SEPARATOR ', ') AS cores, GROUP_CONCAT(DISTINCT st.id_status) AS status_ids, GROUP_CONCAT(DISTINCT st.descricao SEPARATOR ', ') AS status FROM {$this->table} p INNER JOIN shaper s ON p.id_shaper = s.id_shaper INNER JOIN composicao c ON p.id_composicao = c.id_composicao INNER JOIN variacao v ON p.id_variacao = v.id_variacao INNER JOIN acabamento a ON p.id_acabamento = a.id_acabamento LEFT JOIN pedido_tecido pt ON p.id_pedido = pt.id_pedido LEFT JOIN tecido t ON pt.id_tecido = t.id_tecido INNER JOIN configuracaoquilha k ON p.id_configuracaoquilha = k.id_configuracaoquilha INNER JOIN sistemaquilha q ON p.id_sistemaquilha = q.id_sistemaquilha LEFT JOIN pedido_cor pc ON p.id_pedido = pc.id_pedido LEFT JOIN cor co ON pc.id_cor = co.id_cor LEFT JOIN pedido_status ps ON p.id_pedido = ps.id_pedido LEFT JOIN status st ON ps.id_status = st.id_status WHERE p.id_pedido = :id GROUP BY p.id_pedido LIMIT 1 ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function criar($data, $id_shaper, $id_composicao, $id_variacao, $id_acabamento, $id_configuracaoquilha, $id_sistemaquilha, $observacao, $tecidos, $cores, $tipo_bloco = 'PU', $data_prevista_entrega = null) {
        $query = "INSERT INTO {$this->table} (data, tipo_bloco, data_prevista_entrega, id_shaper, id_composicao, id_variacao, id_acabamento, id_configuracaoquilha, id_sistemaquilha, observacao) VALUES (:data, :tipo_bloco, :data_prevista_entrega, :id_shaper, :id_composicao, :id_variacao, :id_acabamento, :id_configuracaoquilha, :id_sistemaquilha, :observacao)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":data", $data); 
        $stmt->bindParam(":tipo_bloco", $tipo_bloco); 
        $stmt->bindParam(":data_prevista_entrega", $data_prevista_entrega); 
        $stmt->bindParam(":id_shaper", $id_shaper); 
        $stmt->bindParam(":id_composicao", $id_composicao); 
        $stmt->bindParam(":id_variacao", $id_variacao); 
        $stmt->bindParam(":id_acabamento", $id_acabamento); 
        $stmt->bindParam(":id_configuracaoquilha", $id_configuracaoquilha); 
        $stmt->bindParam(":id_sistemaquilha", $id_sistemaquilha); 
        $stmt->bindParam(":observacao", $observacao);

        if ($stmt->execute()) {
            $id_pedido = $this->conn->lastInsertId();
            if (!empty($tecidos)) {
                foreach ($tecidos as $tecido) {
                    $st = $this->conn->prepare("INSERT INTO pedido_tecido (id_pedido, id_tecido) VALUES (?, ?)");
                    $st->execute([$id_pedido, $tecido]);
                }
            }
            if (!empty($cores)) {
                foreach ($cores as $cor) {
                    $st = $this->conn->prepare("INSERT INTO pedido_cor (id_pedido, id_cor) VALUES (?, ?)");
                    $st->execute([$id_pedido, $cor]);
                }
            }
            return (int) $id_pedido;
        }
        return false;
    }

    public function atualizar($id, $data, $id_shaper, $id_composicao, $id_variacao, $id_acabamento, $id_configuracaoquilha, $id_sistemaquilha, $observacao, $tecidos, $cores, $tipo_bloco = 'PU', $data_prevista_entrega = null) {
        $query = "UPDATE {$this->table} SET data = :data, tipo_bloco = :tipo_bloco, data_prevista_entrega = :data_prevista_entrega, id_shaper = :id_shaper, id_composicao = :id_composicao, id_variacao = :id_variacao, id_acabamento = :id_acabamento, id_configuracaoquilha = :id_configuracaoquilha, id_sistemaquilha = :id_sistemaquilha, observacao = :observacao WHERE id_pedido = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id); 
        $stmt->bindParam(":data", $data); 
        $stmt->bindParam(":tipo_bloco", $tipo_bloco); 
        $stmt->bindParam(":data_prevista_entrega", $data_prevista_entrega); 
        $stmt->bindParam(":id_shaper", $id_shaper); 
        $stmt->bindParam(":id_composicao", $id_composicao); 
        $stmt->bindParam(":id_variacao", $id_variacao); 
        $stmt->bindParam(":id_acabamento", $id_acabamento); 
        $stmt->bindParam(":id_configuracaoquilha", $id_configuracaoquilha); 
        $stmt->bindParam(":id_sistemaquilha", $id_sistemaquilha); 
        $stmt->bindParam(":observacao", $observacao);
        
        $stmt->execute();

        $this->conn->prepare("DELETE FROM pedido_tecido WHERE id_pedido = ?")->execute([$id]);
        if (!empty($tecidos)) {
            foreach ($tecidos as $tecido) {
                $this->conn->prepare("INSERT INTO pedido_tecido (id_pedido, id_tecido) VALUES (?, ?)")->execute([$id, $tecido]);
            }
        }
        
        $this->conn->prepare("DELETE FROM pedido_cor WHERE id_pedido = ?")->execute([$id]);
        if (!empty($cores)) {
            foreach ($cores as $cor) {
                $this->conn->prepare("INSERT INTO pedido_cor (id_pedido, id_cor) VALUES (?, ?)")->execute([$id, $cor]);
            }
        }
        return true;
    }

    public function deletar($id) {
        try {
            $this->conn->beginTransaction();
            // Limpeza de tabelas filhas
            $this->conn->prepare("DELETE FROM pedido_cor WHERE id_pedido = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM pedido_tecido WHERE id_pedido = ?")->execute([$id]);
            $this->conn->prepare("DELETE FROM pedido_status WHERE id_pedido = ?")->execute([$id]);
            
            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_pedido = ?");
            $stmt->execute([$id]);
            $success = $stmt->rowCount() > 0;
            $this->conn->commit();
            return $success;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }
}