<?php

namespace App\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\Model as LdapModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Log;

class OnlyMonitoringPasienGroup implements Rule
{
    /**
     * Cek apakah user AD adalah anggota grup yang diizinkan.
     * Dipanggil otomatis di dalam Auth::attempt().
     * Return false = login ditolak meski password benar.
     */
    public function passes(LdapModel $user, ?EloquentModel $model = null): bool
    {
        $allowedGroup = config('ldap.allowed_group', 'Monitoring-Pasien');

        // Ambil semua grup termasuk nested/recursive
        $groups = $user->groups()->recursive()->get();

        $groupNames = [];
        foreach ($groups as $group) {
            $cn = $group->getFirstAttribute('cn');
            if (!is_null($cn)) {
                $groupNames[] = $cn;
                if (strtolower($cn) === strtolower($allowedGroup)) {
                    Log::info("LDAP login success untuk user: {$user->getFirstAttribute('samaccountname')} (Grup cocok: {$cn})");
                    return true;
                }
            }
        }

        Log::warning("LDAP login ditolak untuk user: {$user->getFirstAttribute('samaccountname')}. Grup yang dibutuhkan: '{$allowedGroup}'. Grup user di AD: " . implode(', ', $groupNames));
        return false;
    }
}
