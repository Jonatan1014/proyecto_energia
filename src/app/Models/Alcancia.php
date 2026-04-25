<?php
// src/app/Models/Alcancia.php

require_once __DIR__ . '/../../config/database.php';

class Alcancia {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureTables();
    }

    private function ensureTables(): void {
        // Configuracion unica de la alcancia
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS alcancia_config (
                id TINYINT UNSIGNED PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL DEFAULT \'Alcancia Principal\',
                moneda CHAR(3) NOT NULL DEFAULT \'COP\',
                total_ahorrado DECIMAL(12,2) NOT NULL DEFAULT 0,
                meta_general DECIMAL(12,2) NOT NULL DEFAULT 100000,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );

        // Metas de ahorro
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS alcancia_metas (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                alcancia_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                nombre VARCHAR(120) NOT NULL,
                descripcion VARCHAR(255) NULL,
                monto_objetivo DECIMAL(12,2) NOT NULL,
                monto_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
                prioridad TINYINT UNSIGNED NOT NULL DEFAULT 1,
                activa TINYINT(1) NOT NULL DEFAULT 1,
                fecha_objetivo DATE NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_metas_config FOREIGN KEY (alcancia_id) REFERENCES alcancia_config(id) ON DELETE CASCADE,
                INDEX idx_metas_activas (alcancia_id, activa, prioridad)
            ) ENGINE=InnoDB'
        );

        // Depositos enviados por ESP32
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS alcancia_depositos (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                alcancia_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                monto DECIMAL(12,2) NOT NULL,
                pulsos SMALLINT UNSIGNED NULL,
                origen VARCHAR(50) NOT NULL DEFAULT \'esp32\',
                referencia VARCHAR(120) NULL,
                sync_batch TINYINT(1) NOT NULL DEFAULT 0,
                metadata JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_depositos_config FOREIGN KEY (alcancia_id) REFERENCES alcancia_config(id) ON DELETE CASCADE,
                INDEX idx_depositos_fecha (alcancia_id, created_at),
                INDEX idx_depositos_origen (origen)
            ) ENGINE=InnoDB'
        );

        // Historial de retiros / vaciados
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS alcancia_retiros (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                alcancia_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
                monto_retirado DECIMAL(12,2) NOT NULL,
                usuario_id INT UNSIGNED NULL,
                usuario_nombre VARCHAR(150) NOT NULL,
                motivo VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_retiros_config FOREIGN KEY (alcancia_id) REFERENCES alcancia_config(id) ON DELETE CASCADE,
                INDEX idx_retiros_fecha (alcancia_id, created_at),
                INDEX idx_retiros_usuario (usuario_id)
            ) ENGINE=InnoDB'
        );
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

        $stmtRetiros = $this->db->prepare(
            'SELECT id, monto_retirado, usuario_id, usuario_nombre, motivo, created_at
             FROM alcancia_retiros
             WHERE alcancia_id = 1
             ORDER BY id DESC
             LIMIT ?'
        );
        $stmtRetiros->bindValue(1, $limit, PDO::PARAM_INT);
        $stmtRetiros->execute();
        $retiros = $stmtRetiros->fetchAll();

        $stmtStatsRetiros = $this->db->query(
            'SELECT COUNT(*) AS total_retiros, COALESCE(SUM(monto_retirado), 0) AS acumulado_retirado
             FROM alcancia_retiros
             WHERE alcancia_id = 1'
        );
        $statsRetiros = $stmtStatsRetiros->fetch() ?: ['total_retiros' => 0, 'acumulado_retirado' => 0];

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
            'ultimos_retiros' => $retiros,
            'resumen' => [
                'total_depositos' => (int)$stats['total_depositos'],
                'acumulado_depositos' => (float)$stats['acumulado_depositos'],
                'total_retiros' => (int)$statsRetiros['total_retiros'],
                'acumulado_retirado' => (float)$statsRetiros['acumulado_retirado'],
            ],
        ];
    }

    public function actualizarMeta(int $metaId, string $nombre, float $montoObjetivo): array {
        if ($metaId <= 0) {
            throw new InvalidArgumentException('Meta invalida');
        }

        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre de la meta es obligatorio');
        }

        if ($montoObjetivo <= 0) {
            throw new InvalidArgumentException('El monto objetivo debe ser mayor que 0');
        }

        $this->db->beginTransaction();
        try {
            $this->ensureConfig();

            $stmtMeta = $this->db->prepare('SELECT id, monto_actual FROM alcancia_metas WHERE id = ? AND alcancia_id = 1 LIMIT 1');
            $stmtMeta->execute([$metaId]);
            $meta = $stmtMeta->fetch();

            if (!$meta) {
                throw new InvalidArgumentException('La meta no existe');
            }

            $montoActualMeta = (float)($meta['monto_actual'] ?? 0);
            if ($montoObjetivo < $montoActualMeta) {
                throw new InvalidArgumentException('La meta no puede ser menor al progreso actual');
            }

            $config = $this->db->query('SELECT total_ahorrado FROM alcancia_config WHERE id = 1 LIMIT 1')->fetch();
            $totalAhorrado = (float)($config['total_ahorrado'] ?? 0);
            if ($montoObjetivo < $totalAhorrado) {
                throw new InvalidArgumentException('La meta no puede ser menor al dinero ya registrado en la alcancia');
            }

            $stmtUpdate = $this->db->prepare('UPDATE alcancia_metas SET nombre = ?, monto_objetivo = ?, updated_at = NOW() WHERE id = ? AND alcancia_id = 1');
            $stmtUpdate->execute([$nombre, $montoObjetivo, $metaId]);

            $this->db->prepare('UPDATE alcancia_config SET meta_general = ?, updated_at = NOW() WHERE id = 1')
                ->execute([$montoObjetivo]);

            $this->db->commit();
            return $this->getEstado(10);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function retirarDinero(?int $usuarioId, string $usuarioNombre, ?float $montoRetiro = null, ?string $motivo = null): array {
        $usuarioNombre = trim($usuarioNombre);
        if ($usuarioNombre === '') {
            $usuarioNombre = 'Usuario';
        }

        $motivo = $motivo !== null ? trim($motivo) : null;
        if ($motivo === '') {
            $motivo = null;
        }

        $this->db->beginTransaction();
        try {
            $config = $this->ensureConfig();
            $totalActual = (float)($config['total_ahorrado'] ?? 0);
            
            if ($totalActual <= 0) {
                throw new InvalidArgumentException('La alcancía ya está en cero');
            }

            $montoARetirar = $totalActual;
            $esVaciado = true;

            if ($montoRetiro !== null) {
                if ($montoRetiro <= 0) {
                    throw new InvalidArgumentException('El monto a retirar debe ser mayor a 0');
                }
                if ($montoRetiro > $totalActual) {
                    throw new InvalidArgumentException('No hay suficientes fondos. Disponible: ' . $totalActual);
                }
                $montoARetirar = $montoRetiro;
                $esVaciado = (abs($totalActual - $montoRetiro) < 0.01);
            }

            $stmtRetiro = $this->db->prepare(
                'INSERT INTO alcancia_retiros (alcancia_id, monto_retirado, usuario_id, usuario_nombre, motivo)
                 VALUES (1, ?, ?, ?, ?)'
            );
            $stmtRetiro->execute([$montoARetirar, $usuarioId, $usuarioNombre, $motivo]);

            $nuevoTotal = max(0, $totalActual - $montoARetirar);
            $this->db->prepare('UPDATE alcancia_config SET total_ahorrado = ?, updated_at = NOW() WHERE id = 1')->execute([$nuevoTotal]);

            if ($esVaciado) {
                $this->db->prepare('UPDATE alcancia_metas SET monto_actual = 0, updated_at = NOW() WHERE alcancia_id = 1')->execute();
            } else {
                // Restar a las metas progresivamente (de las activas prioridades más bajas o IDs más altos a más bajos)
                $stmtMetas = $this->db->query('SELECT id, monto_actual FROM alcancia_metas WHERE alcancia_id = 1 AND monto_actual > 0 ORDER BY prioridad DESC, id DESC');
                $metas = $stmtMetas->fetchAll();
                
                $montoRestante = $montoARetirar;
                foreach ($metas as $meta) {
                    if ($montoRestante <= 0) {
                        break;
                    }
                    $actual = (float)$meta['monto_actual'];
                    $quitar = min($actual, $montoRestante);
                    
                    $this->db->prepare('UPDATE alcancia_metas SET monto_actual = monto_actual - ?, updated_at = NOW() WHERE id = ?')
                        ->execute([$quitar, $meta['id']]);
                    
                    $montoRestante -= $quitar;
                }
            }

            $this->db->commit();
            return $this->getEstado(10);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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

    public function eliminarRegistros(): array {
        $this->db->beginTransaction();
        try {
            $this->ensureConfig();

            $this->db->exec('DELETE FROM alcancia_depositos WHERE alcancia_id = 1');
            $this->db->exec('DELETE FROM alcancia_retiros WHERE alcancia_id = 1');

            $this->db->commit();
            return $this->getEstado(10);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
