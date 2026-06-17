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
    public function users(): void { $this->ensureChiefAllowed(); $this->crud('users', ['name','email','role','team','password_hash'], 'users/index', 'Utilizadores'); }
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
        $this->crud('items', ['name','designation','unit','weighted_price'], 'items/index', 'Artigos');
    }
    public function inventory(): void
    {
        $this->ensureChiefAllowed();
        if (isset($_GET['delete'])) {
            $this->repo->delete('inventory', (int)$_GET['delete']);
            $this->redirect(Url::page('inventory'));
        }
        $filters = array_intersect_key($_GET, array_flip(['q','item_id','warehouse_id','stock_status']));
        $rows = $this->repo->inventory($filters);
        $this->view('inventory/index', ['title'=>'Inventário','rows'=>$rows,'summary'=>$this->repo->inventorySummary($rows),'filters'=>$filters,'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$this->editRow('inventory')]);
    }
    public function requests(): void
    {
        $user = Auth::user();
        $canManageRequests = $this->canManageRequests($user);
        $edit = $this->editRow('requests');
        if ($edit && !$canManageRequests) {
            $this->redirect(Url::page('requests'));
        }
        $this->view('requests/index', ['title'=>'Requisições','rows'=>$this->repo->requests($user),'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$edit, 'currentUser'=>$user, 'canManageRequests'=>$canManageRequests]);
    }
    public function reports(): void { $user = Auth::user(); $this->view('reports/index', ['title'=>'Gráficos','chartData'=>$this->repo->monthlyByTeam($user), 'currentUser'=>$user]); }
    private function crud(string $table, array $fields, string $view, string $title): void
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
        $this->view($view, ['title'=>$title, 'rows'=>$this->repo->all($table), 'edit'=>$this->editRow($table)]);
    }
    public function saveInventory(): void
    {
        $this->ensureChiefAllowed();
        $data = array_intersect_key($_POST, array_flip(['item_id','warehouse_id','quantity','min_quantity']));
        if (!empty($_POST['id'])) {
            $this->repo->saveInventory($data, 'set');
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
            $line = $_POST['items'][0] ?? $_POST;
            $data = array_intersect_key($line, array_flip(['item_id','warehouse_id','quantity','notes']));
            $data['requester'] = $requester;
            $data['team'] = $team;
            $data['status'] = $status;
            $this->repo->update('requests', (int)$_POST['id'], $data);
        } else {
            foreach (($_POST['items'] ?? []) as $line) {
                if (empty($line['item_id']) || empty($line['quantity'])) {
                    continue;
                }
                $data = array_intersect_key($line, array_flip(['item_id','warehouse_id','quantity','notes']));
                $data['requester'] = $requester;
                $data['team'] = $team;
                $data['status'] = $status;
                $this->repo->insert('requests', $data);
            }
        }
        $this->redirect(Url::page('requests'));
    }
    public function requestAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $action = $_GET['do'] ?? '';
        $request = $this->repo->find('requests', $id);
        if ($request) {
            $user = Auth::user();
            if (!$this->canManageRequests($user)) {
                $this->redirect(Url::page('requests'));
            }
            if ($action === 'approve') {
                $this->repo->setRequestStatus($id, 'Aprovado');
            } elseif ($action === 'deliver') {
                $quantity = isset($_POST['deliver_quantity']) ? (float)$_POST['deliver_quantity'] : ((float)$request['quantity'] - (float)$request['delivered_quantity']);
                $this->repo->deliverRequest($id, $quantity);
            } elseif ($action === 'cancel') {
                $this->repo->setRequestStatus($id, 'Cancelado');
            }
        }
        $this->redirect(Url::page('requests'));
    }

    private function canManageRequests(?array $user = null): bool
    {
        $role = strtolower((string)($user['role'] ?? ''));
        return in_array($role, ['admin', 'compras'], true);
    }

    private function ensureChiefAllowed(): void
    {
        if (strtolower((string)(Auth::user()['role'] ?? '')) === 'chefe') {
            $this->redirect(Url::page('requests'));
        }
    }

    private function editRow(string $table): ?array
    {
        return isset($_GET['edit']) ? $this->repo->find($table, (int)$_GET['edit']) : null;
    }

    public function export(string $type): void
    {
        $this->ensureChiefAllowed();
        $filters = array_intersect_key($_GET, array_flip(['q','item_id','warehouse_id','stock_status']));
        $rows = $this->repo->inventory($filters);
        if ($type === 'excel') {
            header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=inventario.csv');
            $out = fopen('php://output','w'); fputcsv($out, ['Artigo','Armazém','Setor','Localização','Qtd','Unidade','P. Ponderado','Valor']);
            foreach($rows as $r){ fputcsv($out, [$r['item'],$r['warehouse'],$r['section'],$r['location'],$r['quantity'],$r['unit'],$r['weighted_price'],$r['stock_value']]); } exit;
        }
        header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename=inventario.pdf');
        $summary = $this->repo->inventorySummary($rows);
        $pdfText = function ($value): string {
            $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', (string)$value) ?: (string)$value;
            $out = '';
            for ($i = 0, $length = strlen($encoded); $i < $length; $i++) {
                $ord = ord($encoded[$i]);
                if ($encoded[$i] === '(' || $encoded[$i] === ')' || $encoded[$i] === '\\') {
                    $out .= '\\' . $encoded[$i];
                } elseif ($ord < 32 || $ord > 126) {
                    $out .= sprintf('\\%03o', $ord);
                } else {
                    $out .= $encoded[$i];
                }
            }
            return '(' . $out . ')';
        };
        $textAt = fn($x, $y, $size, $text) => "BT /F1 {$size} Tf 1 0 0 1 {$x} {$y} Tm " . $pdfText($text) . " Tj ET\n";
        $content = "0.07 0.09 0.17 rg 0 730 612 62 re f\n";
        $content .= "0.31 0.27 0.90 rg 0 724 612 6 re f\n";
        $content .= "1 1 1 rg\n" . $textAt(42, 766, 20, 'Inventário') . $textAt(42, 744, 10, 'Relatório de stock filtrado');
        $content .= "0.10 0.16 0.28 rg\n" . $textAt(42, 700, 9, 'Linhas: ' . $summary['lines']) . $textAt(170, 700, 9, 'Quantidade total: ' . number_format((float)$summary['quantity'], 2, ',', '.')) . $textAt(360, 700, 9, 'Valor total: EUR ' . number_format((float)$summary['value'], 2, ',', '.'));
        $content .= "0.94 0.96 0.99 rg 36 660 540 24 re f\n0.78 0.82 0.90 RG 36 660 540 24 re S\n0.20 0.25 0.34 rg\n";
        $content .= $textAt(46, 668, 8, 'Artigo') . $textAt(166, 668, 8, 'Armazém') . $textAt(276, 668, 8, 'Setor') . $textAt(366, 668, 8, 'Localização') . $textAt(456, 668, 8, 'Qtd') . $textAt(512, 668, 8, 'Valor');
        $y = 638;
        foreach (array_slice($rows, 0, 30) as $index => $r) {
            $content .= ($index % 2 === 0 ? "0.99 1 1 rg" : "0.96 0.98 1 rg") . " 36 " . ($y - 7) . " 540 22 re f\n";
            $content .= "0.10 0.16 0.28 rg\n";
            $content .= $textAt(46, $y, 7, mb_strimwidth((string)$r['item'], 0, 27, '…', 'UTF-8'));
            $content .= $textAt(166, $y, 7, mb_strimwidth((string)$r['warehouse'], 0, 24, '…', 'UTF-8'));
            $content .= $textAt(276, $y, 7, mb_strimwidth((string)($r['section'] ?? ''), 0, 20, '…', 'UTF-8'));
            $content .= $textAt(366, $y, 7, mb_strimwidth((string)($r['location'] ?? ''), 0, 20, '…', 'UTF-8'));
            $content .= $textAt(456, $y, 7, $r['quantity'] . ' ' . $r['unit']);
            $content .= $textAt(512, $y, 7, 'EUR ' . number_format((float)$r['stock_value'], 2, ',', '.'));
            $y -= 22;
        }
        if (count($rows) > 30) {
            $content .= "0.40 0.45 0.55 rg\n" . $textAt(42, $y - 8, 8, 'Exportação limitada às primeiras 30 linhas no PDF. Use CSV para a lista completa.');
        }
        $content .= "0.55 0.60 0.70 rg\n" . $textAt(42, 34, 8, 'Calçada WMS · inventário exportado em ' . date('d/m/Y H:i'));
        echo "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>>>>>endobj\n4 0 obj<</Length ".strlen($content).">>stream\n".$content."endstream endobj\nxref\n0 5\n0000000000 65535 f\ntrailer<</Root 1 0 R/Size 5>>\n%%EOF"; exit;
    }
}
