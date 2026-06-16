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
    public function dashboard(): void { $this->view('dashboard/index', ['title'=>'Painel', 'stats'=>$this->repo->dashboard(), 'requests'=>$this->repo->requests()]); }
    public function users(): void { $this->crud('users', ['name','email','role','team'], 'users/index', 'Utilizadores'); }
    public function warehouses(): void { $this->crud('warehouses', ['name','section','location'], 'warehouses/index', 'Armazéns'); }
    public function items(): void { $this->crud('items', ['name','designation','unit','weighted_price'], 'items/index', 'Artigos'); }
    public function inventory(): void
    {
        if (isset($_GET['delete'])) {
            $this->repo->delete('inventory', (int)$_GET['delete']);
            $this->redirect(Url::page('inventory'));
        }
        $this->view('inventory/index', ['title'=>'Inventário','rows'=>$this->repo->inventory(),'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses(), 'edit'=>$this->editRow('inventory')]);
    }
    public function requests(): void { $this->view('requests/index', ['title'=>'Requisições','rows'=>$this->repo->requests(),'items'=>$this->repo->items(),'warehouses'=>$this->repo->warehouses()]); }
    public function reports(): void { $this->view('reports/index', ['title'=>'Gráficos','chartData'=>$this->repo->monthlyByTeam()]); }
    private function crud(string $table, array $fields, string $view, string $title): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_intersect_key($_POST, array_flip($fields));
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
        $this->repo->insert('requests', array_intersect_key($_POST, array_flip(['requester','team','item_id','warehouse_id','quantity','status','notes'])));
        $this->redirect(Url::page('requests'));
    }
    public function requestAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $action = $_GET['do'] ?? '';
        $request = $this->repo->find('requests', $id);
        if ($request) {
            if ($action === 'approve') {
                $this->repo->setRequestStatus($id, 'Aprovado');
            } elseif ($action === 'deliver') {
                $this->repo->setRequestStatus($id, 'Entregue');
                if (!empty($request['warehouse_id'])) {
                    $this->repo->adjustInventory((int)$request['item_id'], (int)$request['warehouse_id'], -(float)$request['quantity']);
                }
            } elseif ($action === 'cancel') {
                $this->repo->setRequestStatus($id, 'Cancelado');
            }
        }
        $this->redirect(Url::page('requests'));
    }

    private function editRow(string $table): ?array
    {
        return isset($_GET['edit']) ? $this->repo->find($table, (int)$_GET['edit']) : null;
    }

    public function export(string $type): void
    {
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
