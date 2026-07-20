<?php
namespace App\Core;

class Access
{
    public static function role(array $user): string
    {
        return strtolower(trim((string)($user['role'] ?? '')));
    }

    public static function team(array $user): string
    {
        return strtolower(trim((string)($user['team'] ?? '')));
    }

    public static function canViewAllData(?array $user): bool
    {
        $role = self::role($user ?? []);
        return in_array($role, ['admin', 'rh'], true);
    }

    public static function isMaterialTeam(?array $user): bool
    {
        $role = self::role($user ?? []);
        $team = self::team($user ?? []);
        $scope = $role . ' ' . $team;
        return str_contains($scope, 'tornearia') || str_contains($scope, 'desenho técnico') || str_contains($scope, 'desenho tecnico');
    }

    public static function isMaintenanceTeam(?array $user): bool
    {
        $role = self::role($user ?? []);
        $team = self::team($user ?? []);
        return str_contains($role, 'manuten') || str_contains($team, 'manuten');
    }

    public static function allowedPages(?array $user): array
    {
        $role = self::role($user ?? []);
        if (self::canViewAllData($user)) {
            return ['dashboard','clear_catalog','users','warehouses','items','inventory','inventory_save','requests','request_save','request_action','purchases','purchase_save','purchase_status','purchase_delete','material','material_save','material_status','material_download','maintenance','maintenance_save','maintenance_status','reports','logs','log_action','export_excel','export_pdf','logout'];
        }
        if ($role === 'chefe') {
            return ['dashboard','requests','request_save','request_action','purchases','purchase_save','material','material_save','maintenance','maintenance_save','reports','export_excel','export_pdf','logout'];
        }
        if ($role === 'compras') {
            return ['dashboard','requests','request_save','request_action','purchases','purchase_save','purchase_status','purchase_delete','reports','export_excel','export_pdf','logout'];
        }
        if ($role === 'stock') {
            return ['dashboard','warehouses','items','inventory','inventory_save','requests','request_save','request_action','reports','export_excel','export_pdf','logout'];
        }
        if ($role === 'financeiro') {
            return ['dashboard','material','material_status','material_download','reports','export_excel','export_pdf','logout'];
        }
        if (self::isMaterialTeam($user)) {
            return ['dashboard','requests','request_save','purchases','purchase_save','material','material_save','material_status','material_download','maintenance','maintenance_save','logout'];
        }
        if (self::isMaintenanceTeam($user)) {
            return ['dashboard','maintenance','maintenance_save','maintenance_status','logout'];
        }
        return ['dashboard','requests','request_save','purchases','purchase_save','maintenance','maintenance_save','logout'];
    }

    public static function canAccessPage(?array $user, string $page): bool
    {
        return in_array($page, self::allowedPages($user), true);
    }
}
