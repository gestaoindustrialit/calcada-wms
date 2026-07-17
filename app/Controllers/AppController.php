<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Url;
use App\Models\Repository;

class AppController extends Controller
{
    private Repository $repo;
    public function __construct(){ $this->repo = new Repository(); }
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect(Url::page('dashboard'));
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::attempt($_POST['username'] ?? '', $_POST['password'] ?? '')) {
                $this->redirect(Url::page('dashboard'));
            }
            $error = 'Credenciais inválidas. Use o utilizador admin configurado para o WMS.';
        }
        $this->view('auth/login', ['title'=>'Login admin', 'error'=>$error, 'hideNav'=>true]);
    }
    public function logout(): void
    {
        Auth::logout();
        $this->redirect(Url::page('login'));
    }
    public function dashboard(): void { $user = Auth::user(); $this->view('dashboard/index', ['title'=>'Painel', 'stats'=>$this->repo->dashboard($user), 'requests'=>$this->repo->requests($user), 'currentUser'=>$user]); }
    public function clearCatalog(): void
    {
        $this->ensureAdminAllowed();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm_clear'] ?? '') === 'ELIMINAR') {
            $this->repo->clearItemsAndWarehouses();
            $_SESSION['flash'] = 'Dados de artigos, armazéns, localizações, inventário e requisições eliminados.';
        } else {
            $_SESSION['flash'] = 'Confirmação inválida. Escreva ELIMINAR para apagar os dados.';
        }
        $this->redirect(Url::page('dashboard'));
    }
    public function users(): void { $this->ensureChiefAllowed(); $this->crud('users', ['name','email','role','team','password_hash'], 'users/index', 'Utilizadores', ['roles'=>$this->repo->roles()]); }
    public function warehouses(): void
    {
        $this->ensureChiefAllowed();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'location') {
            $data = array_intersect_key($_POST, array_flip(['warehouse_id','type','code','description']));
            if (!empty($_POST['id'])) { $this->repo->update('warehouse_locations', (int)$_POST['id'], $data); } else { $this->repo->insert('warehouse_locations', $data); }
            $this->redirect(Url::page('warehouses'));
        }
        if (isset($_GET['delete_location'])) { $this->repo->delete('warehouse_locations', (int)$_GET['delete_location']); $this->redirect(Url::page('warehouses')); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_intersect_key($_POST, array_flip(['name','section','location']));
            if (!empty($_POST['id'])) { $this->repo->update('warehouses', (int)$_POST['id'], $data); } else { $this->repo->insert('warehouses', $data); }
            $this->redirect(Url::page('warehouses'));
        }
        if (isset($_GET['delete'])) { $this->repo->delete('warehouses', (int)$_GET['delete']); $this->redirect(Url::page('warehouses')); }
        $this->view('warehouses/index', ['title'=>'Armazéns', 'rows'=>$this->repo->all('warehouses'), 'locations'=>$this->repo->warehouseLocations(), 'edit'=>$this->editRow('warehouses'), 'editLocation'=>isset($_GET['edit_location']) ? $this->repo->find('warehouse_locations', (int)$_GET['edit_location']) : null]);
    }
    public function items(): void
    {
        $this->ensureChiefAllowed();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['items_csv'])) {
            $mode = $_POST['import_mode'] ?? 'quick';
            $result = $this->repo->importItems($_FILES['items_csv'], $mode === 'located');
            $extra = $mode === 'located' ? ', ' . $result['stocked'] . ' linhas de stock/localização' : '';
            $_SESSION['flash'] = 'Importação: ' . $result['created'] . ' criados, ' . $result['updated'] . ' atualizados' . $extra . ($result['errors'] ? ' — ' . implode(' ', $result['errors']) : '.');
            $this->redirect(Url::page('items'));
        }
        $filters = array_intersect_key($_GET, array_flip(['q']));
        $this->crud('items', ['name','designation','unit'], 'items/index', 'Artigos', ['rows'=>$this->repo->items($filters), 'filters'=>$filters]);
    }
    public function inventory(): void
    {
        $this->ensureChiefAllowed();
        if (isset($_GET['delete'])) {
            $this->repo->delete('inventory', (int)$_GET['delete']);
            $this->redirect(Url::page('inventory'));
        }
        $filters = array_intersect_key($_GET, array_flip(['q','item_id','warehouse_id']));
        $rows = $this->repo->inventory($filters);
        $this->view('inventory/index', ['title'=>'Inventário','rows'=>$rows,'summary'=>$this->repo->inventorySummary($rows),'filters'=>$filters,'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$this->editRow('inventory'), 'locations'=>$this->repo->warehouseLocations(), 'inventoryRows'=>$this->repo->inventory()]);
    }
    public function requests(): void
    {
        $user = Auth::user();
        $canManageRequests = $this->canManageRequests($user);
        $edit = $this->editRow('requests');
        if ($edit && !$canManageRequests) {
            $this->redirect(Url::page('requests'));
        }
        $editLines = $edit ? $this->repo->requestGroupLines((int)$edit['id']) : [];
        $sort = $_GET['sort'] ?? 'recent';
        $this->view('requests/index', ['title'=>'Requisições','rows'=>$this->repo->requests($user, $sort),'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$edit, 'editLines'=>$editLines, 'currentUser'=>$user, 'canManageRequests'=>$canManageRequests, 'sort'=>$sort]);
    }

    public function logs(): void
    {
        $this->ensureAdminAllowed();
        $filters = array_intersect_key($_GET, array_flip(['table_name','action','q']));
        $this->view('logs/index', ['title'=>'Logs de ações', 'rows'=>$this->repo->actionLogs($filters), 'filters'=>$filters, 'tables'=>['users','warehouses','warehouse_locations','items','inventory','requests','material_requests','purchase_requests'], 'actions'=>['create','update','delete']]);
    }

    public function logAction(): void
    {
        $this->ensureAdminAllowed();
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $action = $_POST['log_action'] ?? ($_GET['do'] ?? '');
        if ($id > 0 && $action === 'save_note') {
            $this->repo->updateActionLog($id, trim((string)($_POST['note'] ?? '')));
            $_SESSION['flash'] = 'Nota do log atualizada.';
        } elseif ($id > 0 && $action === 'revert') {
            $_SESSION['flash'] = $this->repo->revertActionLog($id) ? 'Modificação anulada com sucesso.' : 'Não foi possível anular esta modificação.';
        }
        $this->redirect(Url::page('logs'));
    }

    public function reports(): void { $user = Auth::user(); $this->view('reports/index', ['title'=>'Gráficos','chartData'=>$this->repo->monthlyByTeam($user), 'currentUser'=>$user]); }

    public function material(): void
    {
        $user = Auth::user();
        $requestedView = $_GET['view'] ?? 'pending';
        $viewMode = in_array($requestedView, ['pending', 'completed', 'billed'], true) ? $requestedView : 'pending';
        $this->view('material/index', ['title'=>'Material', 'rows'=>$this->repo->materialRequests($viewMode), 'viewMode'=>$viewMode, 'currentUser'=>$user, 'canManageMaterial'=>$this->canManageMaterial($user), 'canEditMaterialDetails'=>$this->canEditMaterialDetails($user), 'canInvoiceMaterial'=>$this->canInvoiceMaterial($user)]);
    }

    public function saveMaterial(): void
    {
        $user = Auth::user();
        $data = array_intersect_key($_POST, array_flip(['responsible','department','product','operation','quantity','urgency','due_date','notes']));
        $data['requester_name'] = $user['name'] ?? '';
        $data['requester_team'] = $user['team'] ?? '';
        $data['status'] = 'A Aguardar';
        $data['completed_quantity'] = 0;
        [$data['attachment_name'], $data['attachment_path']] = $this->storeMaterialAttachment($_FILES['attachment'] ?? null);
        $data['billed'] = 0;
        $this->repo->insert('material_requests', $data);
        $this->redirect(Url::page('material'));
    }


    public function materialDownload(): void
    {
        $request = $this->repo->find('material_requests', (int)($_GET['id'] ?? 0));
        $path = $request['attachment_path'] ?? '';
        $name = $request['attachment_name'] ?? 'ficheiro';
        if (!$request || !$path) {
            http_response_code(404);
            exit('Ficheiro não encontrado.');
        }
        $baseDir = realpath(dirname(__DIR__, 2) . '/data/material_uploads');
        $filePath = realpath(dirname(__DIR__, 2) . '/' . $path);
        if (!$baseDir || !$filePath || !str_starts_with($filePath, $baseDir) || !is_file($filePath)) {
            http_response_code(404);
            exit('Ficheiro não encontrado.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function materialStatus(): void
    {
        $user = Auth::user();
        $status = (string)($_POST['status'] ?? '');
        $data = [];
        if ($status !== '' && $this->canManageMaterial($user)) {
            $data['status'] = $status;
            $data['completed_quantity'] = max((float)($_POST['completed_quantity'] ?? 0), 0);
        }
        if ($this->canEditMaterialDetails($user)) {
            $data['due_date'] = (string)($_POST['due_date'] ?? '');
            $data['notes'] = trim((string)($_POST['notes'] ?? ''));
        }
        if ($this->canInvoiceMaterial($user)) {
            $data['billed'] = isset($_POST['billed']) ? 1 : 0;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($data) {
            $this->repo->updateMaterialRequestWorkflow($id, $data);
        }
        $updated = $this->repo->find('material_requests', $id);
        $targetView = !empty($updated['billed']) ? 'billed' : (($updated['status'] ?? '') === 'Concluído' ? 'completed' : 'pending');
        $this->redirect(Url::page('material') . '&view=' . $targetView);
    }

    private function crud(string $table, array $fields, string $view, string $title, array $extraData = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_intersect_key($_POST, array_flip($fields));
            if ($table === 'users') {
                if (!empty($data['password_hash'])) $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_DEFAULT);
                else unset($data['password_hash']);
            }
            if (!empty($_POST['id'])) {
                $this->repo->update($table, (int)$_POST['id'], $data);
            } else {
                $this->repo->insert($table, $data);
            }
            $this->redirect(Url::page($table));
        }
        if (isset($_GET['delete'])) { $this->repo->delete($table, (int)$_GET['delete']); $this->redirect(Url::page($table)); }
        $this->view($view, array_merge(['title'=>$title, 'rows'=>$this->repo->all($table), 'edit'=>$this->editRow($table)], $extraData));
    }
    public function saveInventory(): void
    {
        $this->ensureChiefAllowed();
        $data = array_intersect_key($_POST, array_flip(['item_id','warehouse_id','location','quantity']));
        $data['min_quantity'] = 0;
        if (($_POST['movement_type'] ?? '') === 'split' && empty($_POST['id'])) {
            $this->repo->splitInventory((int)$_POST['item_id'], (int)$_POST['warehouse_id'], (string)($_POST['source_location'] ?? ''), (string)($_POST['location'] ?? ''), (float)($_POST['quantity'] ?? 0), 0);
            $this->redirect(Url::page('inventory'));
        }
        if (!empty($_POST['id'])) {
            $this->repo->setInventoryRow((int)$_POST['id'], $data);
        } else {
            $this->repo->saveInventory($data, $_POST['movement_type'] ?? 'in');
        }
        $this->redirect(Url::page('inventory'));
    }
    public function saveRequest(): void
    {
        $user = Auth::user();
        $canManageRequests = $this->canManageRequests($user);
        $requester = $canManageRequests ? trim((string)($_POST['requester'] ?? '')) : ($user['name'] ?? '');
        $team = $canManageRequests ? trim((string)($_POST['team'] ?? '')) : ($user['team'] ?? '');
        $status = $canManageRequests ? ($_POST['status'] ?? 'Pendente') : 'Pendente';

        if (!empty($_POST['id'])) {
            if (!$canManageRequests) {
                $this->redirect(Url::page('requests'));
            }
            $editableLines = $this->repo->requestGroupLines((int)$_POST['id']);
            $editableLineIds = array_map('intval', array_column($editableLines, 'id'));
            foreach (($_POST['items'] ?? []) as $line) {
                $lineId = (int)($line['id'] ?? 0);
                if (!$lineId || !in_array($lineId, $editableLineIds, true)) {
                    continue;
                }
                $data = array_intersect_key($line, array_flip(['item_id','warehouse_id','quantity','notes']));
                $data['requester'] = $requester;
                $data['team'] = $team;
                $data['status'] = $status;
                $this->repo->update('requests', $lineId, $data);
            }
        } else {
            $requestGroup = bin2hex(random_bytes(8));
            foreach (($_POST['items'] ?? []) as $line) {
                if (empty($line['item_id']) || empty($line['quantity'])) {
                    continue;
                }
                $data = array_intersect_key($line, array_flip(['item_id','warehouse_id','quantity','notes']));
                $data['requester'] = $requester;
                $data['team'] = $team;
                $data['status'] = $status;
                $data['request_group'] = $requestGroup;
                $this->repo->insert('requests', $data);
            }
        }
        $this->redirect(Url::page('requests'));
    }
    public function requestAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $action = $_POST['request_action'] ?? ($_GET['do'] ?? '');
        $request = $this->repo->find('requests', $id);
        if ($request) {
            $user = Auth::user();
            if (!$this->canManageRequests($user)) {
                $this->redirect(Url::page('requests'));
            }
            if ($action === 'approve') {
                $this->repo->setRequestStatus($id, 'Aprovado');
            } elseif ($action === 'deliver') {
                if (!in_array($request['status'], ['Cancelado', 'Entregue'], true)) {
                    $postedQuantities = $_POST['deliver_quantities'] ?? [];
                    $quantity = isset($postedQuantities[$id]) ? (float)$postedQuantities[$id] : (isset($_POST['deliver_quantity']) ? (float)$_POST['deliver_quantity'] : ((float)$request['quantity'] - (float)$request['delivered_quantity']));
                    $this->repo->deliverRequest($id, $quantity);
                }
            } elseif ($action === 'deliver_all') {
                foreach ($this->repo->requestGroupLines($id) as $line) {
                    if (in_array($line['status'], ['Cancelado', 'Entregue'], true)) {
                        continue;
                    }
                    $postedQuantities = $_POST['deliver_quantities'] ?? [];
                    $lineId = (int)$line['id'];
                    $quantity = isset($postedQuantities[$lineId]) ? (float)$postedQuantities[$lineId] : ((float)$line['quantity'] - (float)$line['delivered_quantity']);
                    $this->repo->deliverRequest($lineId, $quantity);
                }
            } elseif ($action === 'cancel') {
                $this->repo->setRequestStatus($id, 'Cancelado');
            } elseif ($action === 'delete') {
                $this->repo->deleteRequestGroup($id);
            }
        }
        $this->redirect(Url::page('requests'));
    }


    private function storeMaterialAttachment(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, null];
        }
        $uploadDir = dirname(__DIR__, 2) . '/data/material_uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $originalName = basename((string)$file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');
        $target = $uploadDir . '/' . $storedName;
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return [$originalName, null];
        }
        return [$originalName, 'data/material_uploads/' . $storedName];
    }

    private function canManageRequests(?array $user = null): bool
    {
        $role = strtolower((string)($user['role'] ?? ''));
        return in_array($role, ['admin', 'compras'], true);
    }

    private function canManageMaterial(?array $user = null): bool
    {
        $role = strtolower((string)($user['role'] ?? ''));
        return $role === 'admin' || $this->isMaterialTeam($user);
    }

    private function canEditMaterialDetails(?array $user = null): bool
    {
        $role = strtolower((string)($user['role'] ?? ''));
        return in_array($role, ['admin', 'financeiro'], true) || $this->isMaterialTeam($user);
    }

    private function canInvoiceMaterial(?array $user = null): bool
    {
        $role = strtolower((string)($user['role'] ?? ''));
        $team = strtolower((string)($user['team'] ?? ''));
        return $role === 'admin' || $role === 'financeiro' || str_contains($team, 'financeiro');
    }

    private function isMaterialTeam(?array $user = null): bool
    {
        $team = strtolower((string)($user['team'] ?? ''));
        return str_contains($team, 'tornearia') || str_contains($team, 'desenho técnico') || str_contains($team, 'desenho tecnico');
    }

    private function ensureChiefAllowed(): void
    {
        if (strtolower((string)(Auth::user()['role'] ?? '')) === 'chefe') {
            $this->redirect(Url::page('requests'));
        }
    }

    private function ensureAdminAllowed(): void
    {
        if (strtolower((string)(Auth::user()['role'] ?? '')) !== 'admin') {
            $this->redirect(Url::page('dashboard'));
        }
    }

    private function editRow(string $table): ?array
    {
        return isset($_GET['edit']) ? $this->repo->find($table, (int)$_GET['edit']) : null;
    }

    public function export(string $type): void
    {
        $this->ensureChiefAllowed();
        $filters = array_intersect_key($_GET, array_flip(['q','item_id','warehouse_id']));
        $rows = $this->repo->inventory($filters);
        if ($type === 'excel') {
            header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="inventario.csv"'); header('X-Content-Type-Options: nosniff');
            $out = fopen('php://output','w'); fputcsv($out, ['Artigo','Armazém','Setor','Localização','Qtd','Unidade']);
            foreach($rows as $r){ fputcsv($out, [$r['item'],$r['warehouse'],$r['section'],$r['location'],$r['quantity'],$r['unit']]); } exit;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="inventario.pdf"');
        header('X-Content-Type-Options: nosniff');
        $summary = $this->repo->inventorySummary($rows);
        $pdfText = function ($value): string {
            $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string)$value) ?: (string)$value;
            $out = '';
            for ($i = 0, $length = strlen($encoded); $i < $length; $i++) {
                $char = $encoded[$i];
                $ord = ord($char);
                $out .= ($char === '(' || $char === ')' || $char === '\\') ? '\\' . $char : (($ord < 32 || $ord > 126) ? sprintf('\\%03o', $ord) : $char);
            }
            return '(' . $out . ')';
        };
        $textAt = fn($x, $y, $size, $text) => "BT /F1 {$size} Tf 1 0 0 1 {$x} {$y} Tm " . $pdfText($text) . " Tj ET\n";
        $pages = [];
        foreach (array_chunk($rows, 26) ?: [[]] as $pageIndex => $chunk) {
            $content = "0.07 0.09 0.17 rg 0 730 612 62 re f\n0.31 0.27 0.90 rg 0 724 612 6 re f\n";
            $content .= "1 1 1 rg\n" . $textAt(42, 766, 20, 'Inventário') . $textAt(42, 744, 10, 'Relatório de stock filtrado');
            $content .= "0.10 0.16 0.28 rg\n" . $textAt(42, 700, 9, 'Linhas: ' . $summary['lines']) . $textAt(170, 700, 9, 'Quantidade total: ' . number_format((float)$summary['quantity'], 2, ',', '.'));
            $content .= "0.94 0.96 0.99 rg 36 660 540 24 re f\n0.78 0.82 0.90 RG 36 660 540 24 re S\n0.20 0.25 0.34 rg\n";
            $content .= $textAt(46, 668, 8, 'Artigo') . $textAt(166, 668, 8, 'Armazém') . $textAt(276, 668, 8, 'Setor') . $textAt(366, 668, 8, 'Localização') . $textAt(456, 668, 8, 'Qtd');
            $y = 638;
            foreach ($chunk as $index => $r) {
                $content .= ($index % 2 === 0 ? "0.99 1 1 rg" : "0.96 0.98 1 rg") . " 36 " . ($y - 7) . " 540 22 re f\n0.10 0.16 0.28 rg\n";
                $content .= $textAt(46, $y, 7, mb_strimwidth((string)$r['item'], 0, 27, '…', 'UTF-8'));
                $content .= $textAt(166, $y, 7, mb_strimwidth((string)$r['warehouse'], 0, 24, '…', 'UTF-8'));
                $content .= $textAt(276, $y, 7, mb_strimwidth((string)($r['section'] ?? ''), 0, 20, '…', 'UTF-8'));
                $content .= $textAt(366, $y, 7, mb_strimwidth((string)($r['location'] ?? ''), 0, 20, '…', 'UTF-8'));
                $content .= $textAt(456, $y, 7, $r['quantity'] . ' ' . $r['unit']);
                $y -= 22;
            }
            $content .= "0.55 0.60 0.70 rg\n" . $textAt(42, 34, 8, 'Calçada WMS · inventário exportado em ' . date('d/m/Y H:i')) . $textAt(520, 34, 8, 'Pág. ' . ($pageIndex + 1));
            $pages[] = $content;
        }
        $objects = ["<</Type/Catalog/Pages 2 0 R>>"];
        $pageObjectIds = [];
        $contentObjectIds = [];
        $pagesKids = '';
        foreach ($pages as $i => $content) {
            $pageObjectIds[$i] = 3 + ($i * 2);
            $contentObjectIds[$i] = 4 + ($i * 2);
            $pagesKids .= $pageObjectIds[$i] . ' 0 R ';
        }
        $objects[] = '<</Type/Pages/Count ' . count($pages) . '/Kids[' . trim($pagesKids) . ']>>';
        foreach ($pages as $i => $content) {
            $objects[] = '<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents ' . $contentObjectIds[$i] . ' 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>>>>>>>';
            $objects[] = '<</Length ' . strlen($content) . ">>stream\n" . $content . "endstream";
        }
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        $pdf .= "trailer<</Root 1 0 R/Size " . (count($objects) + 1) . ">>\nstartxref\n{$xref}\n%%EOF";
        echo $pdf; exit;
    }
}
