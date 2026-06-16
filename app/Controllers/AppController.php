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
        $this->view('inventory/index', ['title'=>'Inventário','rows'=>$this->repo->inventory(),'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$this->editRow('inventory')]);
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
            $this->repo->update('inventory', (int)$_POST['id'], $data);
        } else {
            $this->repo->saveInventory($data);
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
        $rows = $this->repo->inventory();
        if ($type === 'excel') {
            header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=inventario.csv');
            $out = fopen('php://output','w'); fputcsv($out, ['Artigo','Armazém','Qtd','Unidade','P. Ponderado','Valor']);
            foreach($rows as $r){ fputcsv($out, [$r['item'],$r['warehouse'],$r['quantity'],$r['unit'],$r['weighted_price'],$r['stock_value']]); } exit;
        }
        header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename=inventario.pdf');
        $text = "Inventario\n" . implode("\n", array_map(fn($r)=>$r['item'].' - '.$r['warehouse'].' - '.$r['quantity'].' '.$r['unit'].' - EUR '.number_format((float)$r['stock_value'],2), $rows));
        echo "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>>>>>endobj\n4 0 obj<</Length ".(strlen($text)+60).">>stream\nBT /F1 12 Tf 50 750 Td (".str_replace(['(',')',"\n"], ['[',']',') Tj T* ('], $text).") Tj ET\nendstream endobj\nxref\n0 5\n0000000000 65535 f \ntrailer<</Root 1 0 R/Size 5>>\n%%EOF"; exit;
    }
}
