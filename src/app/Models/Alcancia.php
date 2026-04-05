<?php
// src/app/Models/Alcancia.php

require_once __DIR__ . '/../../config/database.php';

class Alcancia {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function ensureConfig(): array {
        $stmt = $this->db->query('SELECT * FROM alcancia_config WHERE id = 1 LIMIT 1');
        $config = $stmt->fetch();

        if (!$config) {
            $this->db->exec("INSERT INTO alcancia_config (id, nombre, total_ahorrado, meta_general, moneda) VALUES (1, 'Alcancia Principal', 0, 100000, 'COP')");
            $stmt = $this->db->query('SELECT * FROM alcancia_config WHERE id = 1 LIMIT 1');
            $config = $stmt->fetch();
        }

        return $config ?: [];
    }

    public function registrarDeposito(array $payload): array {
        $monto = isset($payload['monto']) ? (float)$payload['monto'] : 0;
        $pulsos = isset($payload['pulsos']) ? (int)$payload['pulsos'] : null;
        $origen = trim((string)($payload['origen'] ?? 'esp32'));
        $referencia = trim((string)($payload['referencia'] ?? ''));
        $sync = !empty($payload['sync']) ? 1 : 0;

        if ($monto <= 0) {
            throw new InvalidArgumentException('El campo monto debe ser mayor que 0');
        }

        if ($pulsos !== null && $pulsos < 0) {
            throw new InvalidArgumentException('El campo pulsos no puede ser negativo');
        }

        if ($origen === '') {
            $origen = 'esp32';
        }

        $metadata = [];
        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $metadata = $payload['metadata'];
        }

        $this->db->beginTransaction();
        try {
            $this->ensureConfig();

            $stmtInsert = $this->db->prepare(
                'INSERT INTO alcancia_depositos (alcancia_id, monto, pulsos, origen, referencia, sync_batch, metadata) VALUES (1, ?, ?, ?, ?, ?, ?)'
            );
            $stmtInsert->execute([
                $monto,
                $pulsos,
                $origen,
                $referencia === '' ? null : $referencia,
                $sync,
                empty($metadata) ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
            ]);

            $this->db->prepare('UPDATE alcancia_config SET total_ahorrado = total_ahorrado + ?, updated_at = NOW() WHERE id = 1')
                ->execute([$monto]);

            $restante = $monto;
            $stmtMetas = $this->db->query(
                'SELECT id, monto_objetivo, monto_actual FROM alcancia_metas WHERE alcancia_id = 1 AND activa = 1 AND monto_actual < monto_objetivo ORDER BY prioridad ASC, id ASC'
            );
            $metas = $stmtMetas->fetchAll();

            foreach ($metas as $meta) {
                if ($restante <= 0) {
                    break;
                }

                $faltante = max(0, (float)$meta['monto_objetivo'] - (float)$meta['monto_actual']);
                if ($faltante <= 0) {
                    continue;
                }

                $asignado = min($restante, $faltante);
                $this->db->prepare('UPDATE alcancia_metas SET monto_actual = monto_actual + ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$asignado, $meta['id']]);

                $restante -= $asignado;
            }

            $this->db->commit();
            return $this->getEstado(10);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getEstado(int $limitDepositos = 10): array {
        $limit = max(1, min(100, $limitDepositos));
        $config = $this->ensureConfig();

        $stmtMetas = $this->db->query(
            'SELECT id, nombre, descripcion, monto_objetivo, monto_actual, prioridad, activa, fecha_objetivo, created_at, updated_at
             FROM alcancia_metas
             WHERE alcancia_id = 1
             ORDER BY activa DESC, prioridad ASC, id ASC'
        );
        $metas = $stmtMetas->fetchAll();

        $stmtDepositos = $this->db->prepare(
            'SELECT id, monto, pulsos, origen, referencia, sync_batch, metadata, created_at
             FROM alcancia_depositos
             WHERE alcancia_id = 1
             ORDER BY id DESC
             LIMIT ?'
        );
        $stmtDepositos->bindValue(1, $limit, PDO::PARAM_INT);
        $stmtDepositos->execute();
        $depositos = $stmtDepositos->fetchAll();

        $stmtStats = $this->db->query(
            'SELECT COUNT(*) AS total_depositos, COALESCE(SUM(monto), 0) AS acumulado_depositos
             FROM alcancia_depositos
             WHERE alcancia_id = 1'
        );
        $stats = $stmtStats->fetch() ?: ['total_depositos' => 0, 'acumulado_depositos' => 0];

        $totalAhorrado = (float)($config['total_ahorrado'] ?? 0);
        $metaGeneral = (float)($config['meta_general'] ?? 0);
        $avanceGeneral = $metaGeneral > 0 ? round(($totalAhorrado / $metaGeneral) * 100, 2) : 0;

        return [
            'alcancia' => [
                'id' => 1,
                'nombre' => $config['nombre'] ?? 'Alcancia Principal',
                'moneda' => $config['moneda'] ?? 'COP',
                'total_ahorrado' => $totalAhorrado,
                'meta_general' => $metaGeneral,
                'avance_general_porcentaje' => $avanceGeneral,
                'updated_at' => $config['updated_at'] ?? null,
            ],
            'metas' => $metas,
            'ultimos_depositos' => $depositos,
            'resumen' => [
                'total_depositos' => (int)$stats['total_depositos'],
                'acumulado_depositos' => (float)$stats['acumulado_depositos'],
            ],
        ];
    }

    public function getEstadoDispositivo(): array {
        $estado = $this->getEstado(1);
        $metas = $estado['metas'] ?? [];

        $metaPrincipal = null;
        foreach ($metas as $meta) {
            if (!empty($meta['activa'])) {
                $metaPrincipal = $meta;
                break;
            }
        }

        if ($metaPrincipal === null && !empty($metas)) {
            $metaPrincipal = $metas[0];
        }

        return [
            'total_ahorrado' => (float)($estado['alcancia']['total_ahorrado'] ?? 0),
            'meta_general' => (float)($estado['alcancia']['meta_general'] ?? 0),
            'moneda' => (string)($estado['alcancia']['moneda'] ?? 'COP'),
            'meta_nombre' => (string)($metaPrincipal['nombre'] ?? 'Meta General'),
            'meta_actual' => (float)($metaPrincipal['monto_actual'] ?? 0),
            'meta_objetivo' => (float)($metaPrincipal['monto_objetivo'] ?? ($estado['alcancia']['meta_general'] ?? 0)),
            'ultima_actualizacion' => (string)($estado['alcancia']['updated_at'] ?? date('Y-m-d H:i:s')),
        ];
    }
}
