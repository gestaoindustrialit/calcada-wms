<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class Repository extends Model
{
    private array $allowedTables = ['users','warehouses','warehouse_locations','items','inventory','requests','material_requests','purchase_requests','action_logs'];

    public function all(string $table): array
    {
        $this->guardTable($table);
        return $this->db->query("SELECT * FROM {$table} ORDER BY id DESC")->fetchAll();
    }

    public function find(string $table, int $id): ?array
    {
        $this->guardTable($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insert(string $table, array $data): void
    {
        $this->guardTable($table);
        $cols = array_keys($data);
        $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $this->db->prepare($sql)->execute($data);
        if ($table !== 'action_logs') {
            $id = (int)$this->db->lastInsertId();
            $this->logAction($table, $id, 'create', null, $this->find($table, $id));
        }
    }

    public function update(string $table, int $id, array $data): void
    {
        $this->guardTable($table);
        if (!$data) return;
        $before = $table !== 'action_logs' ? $this->find($table, $id) : null;
        $sets = implode(',', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id")->execute($data);
        if ($table !== 'action_logs') {
            $this->logAction($table, $id, 'update', $before, $this->find($table, $id));
        }
    }

    public function delete(string $table, int $id): void
    {
        $this->guardTable($table);
        $before = $table !== 'action_logs' ? $this->find($table, $id) : null;
        $this->db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
        if ($table !== 'action_logs') {
            $this->logAction($table, $id, 'delete', $before, null);
        }
    }


    public function actionLogs(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['table_name'])) { $where[] = 'table_name = :table_name'; $params['table_name'] = $filters['table_name']; }
        if (!empty($filters['action'])) { $where[] = 'action = :action'; $params['action'] = $filters['action']; }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(table_name LIKE :q OR action LIKE :q OR user_name LIKE :q OR user_role LIKE :q OR before_data LIKE :q OR after_data LIKE :q OR note LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        $sql = 'SELECT * FROM action_logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function actionLog(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM action_logs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateActionLog(int $id, string $note): void
    {
        $this->db->prepare('UPDATE action_logs SET note = :note WHERE id = :id')->execute(['note'=>$note, 'id'=>$id]);
    }

    public function revertActionLog(int $id): bool
    {
        $log = $this->actionLog($id);
        if (!$log || (int)$log['reverted'] === 1) return false;
        $table = (string)$log['table_name'];
        $this->guardTable($table);
        if ($table === 'action_logs') return false;
        $rowId = (int)$log['row_id'];
        $before = $log['before_data'] ? json_decode($log['before_data'], true) : null;
        $this->db->beginTransaction();
        try {
            if ($log['action'] === 'create') {
                $this->db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$rowId]);
            } elseif ($log['action'] === 'delete' && is_array($before)) {
                $this->restoreRow($table, $before);
            } elseif ($log['action'] === 'update' && is_array($before)) {
                $this->restoreRow($table, $before);
            } else {
                $this->db->rollBack();
                return false;
            }
            $this->db->prepare('UPDATE action_logs SET reverted = 1, reverted_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$id]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function restoreRow(string $table, array $row): void
    {
        $this->guardTable($table);
        $cols = array_keys($row);
        $sets = implode(',', array_map(fn($col) => "{$col} = :{$col}", $cols));
        $exists = $this->find($table, (int)$row['id']);
        if ($exists) {
            $this->db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id")->execute($row);
            return;
        }
        $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (:" . implode(',:', $cols) . ")";
        $this->db->prepare($sql)->execute($row);
    }

    private function logAction(string $table, int $rowId, string $action, ?array $before, ?array $after): void
    {
        if (!$before && !$after) return;
        $user = Auth::user();
        $this->db->prepare('INSERT INTO action_logs (table_name,row_id,action,before_data,after_data,user_name,user_role) VALUES (:table_name,:row_id,:action,:before_data,:after_data,:user_name,:user_role)')->execute([
            'table_name'=>$table,
            'row_id'=>$rowId,
            'action'=>$action,
            'before_data'=>$before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after_data'=>$after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'user_name'=>$user['name'] ?? 'Sistema',
            'user_role'=>$user['role'] ?? '',
        ]);
    }

    public function userByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :login OR name = :login LIMIT 1');
        $stmt->execute(['login'=>$login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function items(array $filters = []): array
    {
        $where = '';
        $params = [];
        if (($filters['q'] ?? '') !== '') {
            $where = ' WHERE name LIKE :q OR designation LIKE :q OR unit LIKE :q';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        $stmt = $this->db->prepare('SELECT * FROM items' . $where . ' ORDER BY name ASC, designation ASC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function warehouses(): array { return $this->all('warehouses'); }

    public function roles(): array
    {
        $rows = $this->db->query("SELECT DISTINCT role FROM users WHERE TRIM(role) != '' ORDER BY role ASC")->fetchAll();
        $roles = array_values(array_unique(array_filter(array_merge(['Admin', 'Chefe', 'Compras', 'Stock'], array_column($rows, 'role')))));
        natcasesort($roles);
        return array_values($roles);
    }

    public function clearItemsAndWarehouses(): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec('DELETE FROM requests');
            $this->db->exec('DELETE FROM inventory');
            $this->db->exec('DELETE FROM warehouse_locations');
            $this->db->exec('DELETE FROM items');
            $this->db->exec('DELETE FROM warehouses');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function warehouseLocations(?int $warehouseId = null): array
    {
        $where = $warehouseId ? 'WHERE warehouse_locations.warehouse_id = :warehouse_id' : '';
        $stmt = $this->db->prepare("SELECT warehouse_locations.*, warehouses.name AS warehouse FROM warehouse_locations JOIN warehouses ON warehouses.id = warehouse_locations.warehouse_id {$where} ORDER BY warehouses.name, warehouse_locations.type, warehouse_locations.code");
        $stmt->execute($warehouseId ? ['warehouse_id'=>$warehouseId] : []);
        return $stmt->fetchAll();
    }

    public function saveInventory(array $data, string $movementType = 'in'): void
    {
        $quantity = abs((float)($data['quantity'] ?? 0));
        if ($movementType !== 'set' && $quantity <= 0) {
            return;
        }

        $delta = $movementType === 'out' ? -$quantity : $quantity;
        $data['quantity'] = $movementType === 'set' ? (float)($data['quantity'] ?? 0) : $delta;
        $data['location'] = trim((string)($data['location'] ?? ''));

        $stmt = $this->db->prepare('SELECT id, quantity FROM inventory WHERE item_id = ? AND warehouse_id = ? AND location = ? ORDER BY id ASC');
        $stmt->execute([(int)$data['item_id'], (int)$data['warehouse_id'], $data['location']]);
        $existingRows = $stmt->fetchAll();
        if ($existingRows) {
            $primary = $existingRows[0];
            $before = $this->find('inventory', (int)$primary['id']);
            $currentQuantity = array_sum(array_map(fn($row) => (float)$row['quantity'], $existingRows));
            $newQuantity = $movementType === 'set' ? (float)$data['quantity'] : max($currentQuantity + (float)$data['quantity'], 0);

            $ownsTransaction = !$this->db->inTransaction();
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }
            try {
                $this->db->prepare('UPDATE inventory SET quantity = :quantity, min_quantity = :min_quantity WHERE id = :id')->execute([
                    'quantity'=>max($newQuantity, 0), 'min_quantity'=>(float)$data['min_quantity'], 'id'=>(int)$primary['id'],
                ]);
                $duplicateIds = array_map(fn($row) => (int)$row['id'], array_slice($existingRows, 1));
                if ($duplicateIds) {
                    $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));
                    $this->db->prepare("DELETE FROM inventory WHERE id IN ({$placeholders})")->execute($duplicateIds);
                }
                if ($ownsTransaction) {
                    $this->db->commit();
                }
            } catch (\Throwable $e) {
                if ($ownsTransaction && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
            $this->logAction('inventory', (int)$primary['id'], 'update', $before, $this->find('inventory', (int)$primary['id']));
            return;
        }

        if ($movementType === 'out') {
            return;
        }
        $data['quantity'] = max((float)$data['quantity'], 0);
        $this->insert('inventory', $data);
    }

    public function setInventoryRow(int $id, array $data): void
    {
        $before = $this->find('inventory', $id);
        $data['location'] = trim((string)($data['location'] ?? ''));
        $this->db->prepare('UPDATE inventory SET item_id = :item_id, warehouse_id = :warehouse_id, location = :location, quantity = MAX(:quantity, 0), min_quantity = :min_quantity WHERE id = :id')->execute([
            'item_id'=>(int)$data['item_id'],
            'warehouse_id'=>(int)$data['warehouse_id'],
            'location'=>$data['location'],
            'quantity'=>(float)$data['quantity'],
            'min_quantity'=>(float)$data['min_quantity'],
            'id'=>$id,
        ]);
        $this->logAction('inventory', $id, 'update', $before, $this->find('inventory', $id));
    }

    public function adjustInventory(int $itemId, int $warehouseId, float $quantity, string $location = ''): void
    {
        $location = trim($location);
        $select = $this->db->prepare('SELECT * FROM inventory WHERE item_id = ? AND warehouse_id = ? AND location = ? LIMIT 1');
        $select->execute([$itemId, $warehouseId, $location]);
        $before = $select->fetch() ?: null;
        $stmt = $this->db->prepare('UPDATE inventory SET quantity = MAX(quantity + :quantity, 0) WHERE item_id = :item_id AND warehouse_id = :warehouse_id AND location = :location');
        $stmt->execute(['quantity'=>$quantity, 'item_id'=>$itemId, 'warehouse_id'=>$warehouseId, 'location'=>$location]);
        if ($before) $this->logAction('inventory', (int)$before['id'], 'update', $before, $this->find('inventory', (int)$before['id']));
    }

    public function splitInventory(int $itemId, int $warehouseId, string $fromLocation, string $toLocation, float $quantity, float $minQuantity = 0): void
    {
        $fromLocation = trim($fromLocation);
        $toLocation = trim($toLocation);
        $quantity = abs($quantity);
        if ($quantity <= 0 || $toLocation === '' || $fromLocation === $toLocation) {
            return;
        }
        $this->db->beginTransaction();
        try {
            $this->saveInventory(['item_id'=>$itemId, 'warehouse_id'=>$warehouseId, 'location'=>$fromLocation, 'quantity'=>$quantity, 'min_quantity'=>$minQuantity], 'out');
            $this->saveInventory(['item_id'=>$itemId, 'warehouse_id'=>$warehouseId, 'location'=>$toLocation, 'quantity'=>$quantity, 'min_quantity'=>$minQuantity], 'in');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function inventory(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['item_id'])) { $where[] = 'inventory.item_id = :item_id'; $params['item_id'] = (int)$filters['item_id']; }
        if (!empty($filters['warehouse_id'])) { $where[] = 'inventory.warehouse_id = :warehouse_id'; $params['warehouse_id'] = (int)$filters['warehouse_id']; }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(items.name LIKE :q OR items.designation LIKE :q OR warehouses.name LIKE :q OR warehouses.section LIKE :q OR warehouses.location LIKE :q OR inventory.location LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        $sql = "SELECT inventory.*, items.name AS item, items.designation, items.unit, 0 AS weighted_price, warehouses.name AS warehouse, warehouses.section, COALESCE(NULLIF(inventory.location, ''), warehouses.location) AS location,
            0 AS stock_value
            FROM inventory JOIN items ON items.id=inventory.item_id JOIN warehouses ON warehouses.id=inventory.warehouse_id";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY items.name ASC, warehouses.name ASC, location ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function inventorySummary(array $rows): array
    {
        return [
            'lines' => count($rows),
            'quantity' => array_sum(array_map(fn($r) => (float)$r['quantity'], $rows)),
            'value' => 0,
        ];
    }

    public function requests(?array $user = null, string $sort = 'recent'): array
    {
        $where = '';
        $params = [];
        if ($user && !in_array(strtolower((string)($user['role'] ?? '')), ['admin', 'compras'], true)) {
            $where = 'WHERE requests.requester = :name';
            $params = ['name'=>$user['name'] ?? ''];
        }
        $orders = [
            'recent' => 'requests.created_at DESC, requests.id DESC',
            'oldest' => 'requests.created_at ASC, requests.id ASC',
            'status' => 'requests.status ASC, requests.created_at DESC',
            'requester' => 'requests.requester ASC, requests.created_at DESC',
            'team' => 'requests.team ASC, requests.created_at DESC',
            'value_desc' => 'request_value DESC, requests.created_at DESC',
            'value_asc' => 'request_value ASC, requests.created_at DESC',
        ];
        $orderBy = $orders[$sort] ?? $orders['recent'];
        $stmt = $this->db->prepare("SELECT requests.*, items.name AS item, items.designation, items.weighted_price, warehouses.name AS warehouse,
            (requests.quantity * items.weighted_price) AS request_value,
            ((requests.quantity - requests.delivered_quantity) * items.weighted_price) AS pending_value
            FROM requests JOIN items ON items.id=requests.item_id
            LEFT JOIN warehouses ON warehouses.id=requests.warehouse_id
            {$where}
            ORDER BY {$orderBy}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }


    public function requestGroupLines(int $id): array
    {
        $request = $this->find('requests', $id);
        if (!$request) return [];
        if (!empty($request['request_group'])) {
            $stmt = $this->db->prepare('SELECT * FROM requests WHERE request_group = ? ORDER BY id ASC');
            $stmt->execute([$request['request_group']]);
            return $stmt->fetchAll();
        }
        return [$request];
    }

    public function deliverRequest(int $id, float $quantity): void
    {
        $request = $this->find('requests', $id);
        if (!$request) return;
        $remaining = max(0, (float)$request['quantity'] - (float)$request['delivered_quantity']);
        $delivered = min(max($quantity, 0), $remaining);
        if ($delivered <= 0) return;
        $newDelivered = (float)$request['delivered_quantity'] + $delivered;
        $status = $newDelivered >= (float)$request['quantity'] ? 'Entregue' : 'Parcial';
        $before = $request;
        $this->db->prepare('UPDATE requests SET delivered_quantity = :delivered, status = :status WHERE id = :id')->execute(['delivered'=>$newDelivered,'status'=>$status,'id'=>$id]);
        $this->logAction('requests', $id, 'update', $before, $this->find('requests', $id));
        if (!empty($request['warehouse_id'])) $this->adjustInventory((int)$request['item_id'], (int)$request['warehouse_id'], -$delivered);
    }

    public function setRequestStatus(int $id, string $status): void
    {
        $request = $this->find('requests', $id);
        if ($request && !empty($request['request_group'])) {
            foreach ($this->requestGroupLines($id) as $line) { $before = $line; $this->db->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$status, $line['id']]); $this->logAction('requests', (int)$line['id'], 'update', $before, $this->find('requests', (int)$line['id'])); }
            return;
        }
        $before = $request;
        $this->db->prepare('UPDATE requests SET status = ? WHERE id = ?')->execute([$status, $id]);
        $this->logAction('requests', $id, 'update', $before, $this->find('requests', $id));
    }

    public function deleteRequestGroup(int $id): void
    {
        $request = $this->find('requests', $id);
        if (!$request) return;
        if (!empty($request['request_group'])) {
            foreach ($this->requestGroupLines($id) as $line) {
                $this->delete('requests', (int)$line['id']);
            }
            return;
        }
        $this->delete('requests', $id);
    }

    public function purchaseRequests(string $view = 'pending'): array
    {
        $completed = $view === 'completed';
        $operator = $completed ? 'IN' : 'NOT IN';
        $stmt = $this->db->prepare("SELECT purchase_requests.*, (SELECT COUNT(*) FROM purchase_request_history WHERE purchase_request_id = purchase_requests.id) AS history_count FROM purchase_requests WHERE status {$operator} ('Entregue','Cancelado') ORDER BY urgency DESC, created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function purchaseHistories(array $purchaseIds): array
    {
        $purchaseIds = array_values(array_unique(array_filter(array_map('intval', $purchaseIds))));
        if (!$purchaseIds) return [];
        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM purchase_request_history WHERE purchase_request_id IN ({$placeholders}) ORDER BY changed_at DESC, id DESC");
        $stmt->execute($purchaseIds);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int)$row['purchase_request_id']][] = $row;
        }
        return $grouped;
    }

    public function setPurchaseStatus(int $id, string $status): void
    {
        $request = $this->find('purchase_requests', $id);
        if (!$request || $request['status'] === $status) return;
        $before = $request;
        $user = Auth::user();
        $this->db->prepare('UPDATE purchase_requests SET status = :status, status_changed_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['status'=>$status, 'id'=>$id]);
        $this->db->prepare('INSERT INTO purchase_request_history (purchase_request_id,old_status,new_status,changed_by,changed_role) VALUES (:id,:old,:new,:by,:role)')->execute([
            'id'=>$id, 'old'=>$before['status'] ?? '', 'new'=>$status, 'by'=>$user['name'] ?? 'Sistema', 'role'=>$user['role'] ?? '',
        ]);
        $this->logAction('purchase_requests', $id, 'update', $before, $this->find('purchase_requests', $id));
    }

    public function materialRequests(string $view = 'pending'): array
    {
        $completed = $view === 'completed';
        $statuses = "'Concluído','Faturado'";
        $operator = $completed ? 'IN' : 'NOT IN';
        $stmt = $this->db->prepare("SELECT * FROM material_requests WHERE status {$operator} ({$statuses}) ORDER BY due_date ASC, urgency DESC, created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateMaterialRequestWorkflow(int $id, array $data): void
    {
        $request = $this->find('material_requests', $id);
        if (!$request || !$data) return;
        $before = $request;
        $sets = implode(',', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE material_requests SET {$sets} WHERE id = :id")->execute($data);
        $this->logAction('material_requests', $id, 'update', $before, $this->find('material_requests', $id));
    }

    public function importItems(array $file, bool $withLocation = false): array
    {
        $result = ['created'=>0,'updated'=>0,'stocked'=>0,'errors'=>[]];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { $result['errors'][] = 'Ficheiro inválido.'; return $result; }
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) { $result['errors'][] = 'Não foi possível abrir o CSV.'; return $result; }

        $delimiter = ';';
        $headerMap = null;
        $defaultWarehouse = $withLocation ? $this->singleWarehouseName() : null;
        $line = 0;
        $stockTotals = [];
        while (($raw = fgets($handle)) !== false) {
            $line++;
            $raw = $this->normalizeCsvEncoding($raw);
            if ($line === 1) {
                $delimiter = $this->detectCsvDelimiter($raw);
            }
            $row = array_map('trim', str_getcsv($raw, $delimiter));
            if (count($row) === 1) {
                foreach ([';', ',', "\t"] as $fallbackDelimiter) {
                    if ($fallbackDelimiter === $delimiter) continue;
                    $fallbackRow = array_map('trim', str_getcsv($raw, $fallbackDelimiter));
                    if (count($fallbackRow) > count($row)) {
                        $row = $fallbackRow;
                        $delimiter = $fallbackDelimiter;
                    }
                }
            }
            if (!$row || implode('', $row) === '') continue;

            if ($line === 1 && $this->looksLikeImportHeader($row)) {
                $headerMap = $this->csvHeaderMap($row);
                continue;
            }

            $values = $headerMap ? $this->csvValuesByHeader($row, $headerMap) : $this->csvValuesByPosition($row);
            $name = $values['name'];
            $designation = $values['designation'];
            $unit = $values['unit'];
            $price = 0.0;
            $warehouse = $values['warehouse'];
            [$section, $location] = $this->normalizeImportLocation($values['section'], $values['location']);
            if ($warehouse === '' && $defaultWarehouse !== null) {
                $warehouse = $defaultWarehouse;
            }
            $quantity = $this->csvNumber($values['quantity']);
            $minQuantity = 0.0;

            if ($name === '') { $result['errors'][] = "Linha {$line}: nome em falta."; continue; }
            $stmt = $this->db->prepare('SELECT id FROM items WHERE name = ? LIMIT 1');
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            $data = ['name'=>$name,'designation'=>$designation ?: $name,'unit'=>$unit ?: 'un','weighted_price'=>$price];
            if ($id) {
                $this->update('items', (int)$id, $data);
                $itemId = (int)$id;
                $result['updated']++;
            } else {
                $this->insert('items', $data);
                $stmt = $this->db->prepare('SELECT id FROM items WHERE name = ? LIMIT 1');
                $stmt->execute([$name]);
                $itemId = (int)$stmt->fetchColumn();
                $result['created']++;
            }
            $hasLocationData = $warehouse !== '' || $section !== '' || $location !== '' || $values['quantity'] !== '';
            if ($withLocation || $hasLocationData) {
                if ($warehouse === '') {
                    $result['errors'][] = $withLocation
                        ? "Linha {$line}: armazém em falta; preencha a coluna armazém ou deixe apenas um armazém criado para ser usado por defeito."
                        : "Linha {$line}: armazém em falta.";
                    continue;
                }
                $warehouseId = $this->findOrCreateWarehouse($warehouse, $section);
                if ($section !== '') $this->findOrCreateWarehouseLocation($warehouseId, 'Setor', $section, $section);
                if ($location !== '') $this->findOrCreateWarehouseLocation($warehouseId, 'Posição', $location, $location);
                $stockKey = $itemId . '|' . $warehouseId . '|' . $location;
                $stockTotals[$stockKey] = ($stockTotals[$stockKey] ?? 0.0) + $quantity;
                $this->saveInventory(['item_id'=>$itemId,'warehouse_id'=>$warehouseId,'location'=>$location,'quantity'=>$stockTotals[$stockKey],'min_quantity'=>$minQuantity], 'set');
                $result['stocked']++;
            }
        }
        fclose($handle);
        return $result;
    }

    private function normalizeCsvEncoding(string $line): string
    {
        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            $line = substr($line, 3);
        }
        if (mb_check_encoding($line, 'UTF-8')) {
            return $line;
        }
        return mb_convert_encoding($line, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = [';' => 0, "\t" => 0, ',' => 0];
        foreach ($delimiters as $delimiter => $_) {
            $columns = str_getcsv($line, $delimiter);
            $delimiters[$delimiter] = count($columns);
        }
        arsort($delimiters);
        return (string)array_key_first($delimiters);
    }

    private function singleWarehouseName(): ?string
    {
        $stmt = $this->db->query('SELECT name FROM warehouses ORDER BY id ASC LIMIT 2');
        $warehouses = $stmt->fetchAll();
        return count($warehouses) === 1 ? (string)$warehouses[0]['name'] : null;
    }

    private function csvValuesByPosition(array $row): array
    {
        [$name,$designation,$unit,$warehouse,$section,$location,$quantity] = array_pad($row, 7, '');
        $price = '';
        $minQuantity = '';
        return compact('name','designation','unit','price','warehouse','section','location','quantity','minQuantity') + ['min_quantity'=>$minQuantity];
    }

    private function csvValuesByHeader(array $row, array $headerMap): array
    {
        $get = fn(string $key): string => isset($headerMap[$key], $row[$headerMap[$key]]) ? trim((string)$row[$headerMap[$key]]) : '';
        return ['name'=>$get('name'),'designation'=>$get('designation'),'unit'=>$get('unit'),'price'=>$get('price'),'warehouse'=>$get('warehouse'),'section'=>$get('section'),'location'=>$get('location'),'quantity'=>$get('quantity'),'min_quantity'=>$get('min_quantity')];
    }

    private function looksLikeImportHeader(array $row): bool
    {
        return (bool)array_filter($row, fn($cell) => in_array($this->normalizeCsvHeader($cell), ['nome','name','artigo','referencia','armazem','warehouse','quantidade','qtd'], true));
    }

    private function csvHeaderMap(array $headers): array
    {
        $aliases = [
            'name'=>['nome','name','artigo','referencia','ref'],
            'designation'=>['designacao','descricao','description','designation'],
            'unit'=>['unidade','unit','un'],
            'price'=>['preco','preco_ponderado','p_ponderado','weighted_price','price'],
            'warehouse'=>['armazem','warehouse','deposito'],
            'section'=>['setor','sector','section','seccao'],
            'location'=>['localizacao','localizacao_posicao','localizacao_pos','local_posicao','local','location','position','posicao','pos'],
            'quantity'=>['qtd','quantidade','quantity','stock'],
            'min_quantity'=>['min','minimo','quantidade_minima','min_quantity'],
        ];
        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeCsvHeader($header);
            foreach ($aliases as $field => $names) if (in_array($normalized, $names, true)) $map[$field] = $index;
        }
        return $map;
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = trim($this->normalizeCsvEncoding($header));

        // Não depender do comportamento de transliteração do iconv/locale do servidor.
        // Garante que cabeçalhos como armazém, designação, preço e localização
        // são sempre convertidos para armazem, designacao, preco e localizacao.
        $header = strtr($header, [
            'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A',
            'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
            'Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
            'Ç'=>'C','Ñ'=>'N',
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n',
        ]);

        return trim((string)preg_replace('/[^a-z0-9]+/', '_', strtolower($header)), '_');
    }

    private function csvNumber(string $value): float
    {
        $value = trim($value);
        if ($value === '') return 0.0;
        if (str_contains($value, ',') && str_contains($value, '.')) $value = str_replace('.', '', $value);
        return (float)str_replace(',', '.', $value);
    }

    private function normalizeImportLocation(string $section, string $location): array
    {
        $section = trim($section);
        $location = trim($location);
        if (str_contains($location, '|')) {
            [$locationSection, $locationCode] = array_map('trim', explode('|', $location, 2));
            if ($section === '' && !in_array($locationSection, ['', '-', '--'], true)) $section = $locationSection;
            if ($locationCode !== '') $location = $locationCode;
        }
        if (in_array($section, ['-', '--'], true)) $section = '';
        if (in_array($location, ['-', '--'], true)) $location = '';
        return [$section, $location];
    }

    private function findOrCreateWarehouse(string $name, string $section = '', string $location = ''): int
    {
        $stmt = $this->db->prepare('SELECT * FROM warehouses WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$name]);
        $warehouse = $stmt->fetch();
        if ($warehouse) {
            $updates = [];
            if ($section !== '' && in_array(trim((string)$warehouse['section']), ['', '-'], true)) $updates['section'] = $section;
            if ($location !== '' && in_array(trim((string)$warehouse['location']), ['', '-'], true)) $updates['location'] = $location;
            if ($updates) $this->update('warehouses', (int)$warehouse['id'], $updates);
            return (int)$warehouse['id'];
        }
        $this->insert('warehouses', ['name'=>$name, 'section'=>$section ?: '-', 'location'=>$location ?: '-']);
        $stmt = $this->db->prepare('SELECT id FROM warehouses WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$name]);
        return (int)$stmt->fetchColumn();
    }

    private function findOrCreateWarehouseLocation(int $warehouseId, string $type, string $code, string $description = ''): void
    {
        $stmt = $this->db->prepare('SELECT id FROM warehouse_locations WHERE warehouse_id = ? AND LOWER(type) = LOWER(?) AND LOWER(code) = LOWER(?) LIMIT 1');
        $stmt->execute([$warehouseId, $type, $code]);
        if ($stmt->fetchColumn()) return;
        $this->insert('warehouse_locations', ['warehouse_id'=>$warehouseId, 'type'=>$type, 'code'=>$code, 'description'=>$description]);
    }

    public function spendingByPeriod(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'AND requests.requester = :name' : '';
        $params = $filter ? ['name'=>$user['name'] ?? ''] : [];
        $sql = "SELECT
            COALESCE(SUM(CASE WHEN date(requests.created_at) >= date('now','-6 days') THEN requests.quantity*items.weighted_price END),0) week,
            COALESCE(SUM(CASE WHEN strftime('%Y-%m',requests.created_at)=strftime('%Y-%m','now') THEN requests.quantity*items.weighted_price END),0) month,
            COALESCE(SUM(CASE WHEN strftime('%Y',requests.created_at)=strftime('%Y','now') THEN requests.quantity*items.weighted_price END),0) year
            FROM requests JOIN items ON items.id=requests.item_id WHERE requests.status != 'Cancelado' {$filter}";
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetch() ?: ['week'=>0,'month'=>0,'year'=>0];
    }

    public function articleSpend(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'WHERE requests.requester = :name' : '';
        $stmt = $this->db->prepare("SELECT items.name item, ROUND(SUM(requests.quantity*items.weighted_price),2) total FROM requests JOIN items ON items.id=requests.item_id {$filter} GROUP BY items.id ORDER BY total DESC LIMIT 8");
        $stmt->execute($filter ? ['name'=>$user['name'] ?? ''] : []); return $stmt->fetchAll();
    }


    public function monthlyByTeam(?array $user = null): array
    {
        $filter = $this->isChief($user) ? 'WHERE requests.requester = :name' : '';
        $stmt = $this->db->prepare("SELECT strftime('%Y-%m', requests.created_at) AS month, requests.team,
            ROUND(SUM(requests.quantity * items.weighted_price),2) AS total
            FROM requests JOIN items ON items.id=requests.item_id
            {$filter}
            GROUP BY month, requests.team ORDER BY month ASC");
        $stmt->execute($filter ? ['name'=>$user['name'] ?? ''] : []);
        return $stmt->fetchAll();
    }

    public function dashboard(?array $user = null): array
    {
        return ['users'=>(int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),'warehouses'=>(int)$this->db->query('SELECT COUNT(*) FROM warehouses')->fetchColumn(),'items'=>(int)$this->db->query('SELECT COUNT(*) FROM items')->fetchColumn(),'stock_value'=>(float)$this->db->query('SELECT COALESCE(SUM(inventory.quantity*items.weighted_price),0) FROM inventory JOIN items ON items.id=inventory.item_id')->fetchColumn(),'spending'=>$this->spendingByPeriod($user),'article_spend'=>$this->articleSpend($user)];
    }

    private function isChief(?array $user = null): bool
    {
        return strtolower((string)($user['role'] ?? '')) === 'chefe';
    }

    private function guardTable(string $table): void
    {
        if (!in_array($table, $this->allowedTables, true)) throw new \InvalidArgumentException('Tabela inválida.');
    }
}
