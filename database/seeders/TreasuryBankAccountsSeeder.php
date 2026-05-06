<?php

namespace Database\Seeders;

use App\Models\GlAccount;
use App\Models\TreasuryBankAccount;
use Illuminate\Database\Seeder;

class TreasuryBankAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $balanceAsOf = '2026-01-31';

        $accounts = [
            // PESO ACCOUNTS
            ['gl' => '111200001', 'bank' => 'BDO',           'short' => 'BDO SA',    'acct' => '0043-9002-4240',      'type' => 'SA', 'cur' => 'PHP', 'bal' => 68110.45],
            ['gl' => '111200030', 'bank' => 'BDO',           'short' => 'BDO CA',    'acct' => '0026-6801-6818',      'type' => 'CA', 'cur' => 'PHP', 'bal' => 26285926.63],
            ['gl' => '111200003', 'bank' => 'UNIONBANK',     'short' => 'UB CA',     'acct' => '0022-8000-4829',      'type' => 'CA', 'cur' => 'PHP', 'bal' => 295185.49],
            ['gl' => '111200033', 'bank' => 'SECURITY BANK', 'short' => 'SBC CA1',   'acct' => '0111058548001',       'type' => 'CA', 'cur' => 'PHP', 'bal' => 2624159.88],
            ['gl' => '111200035', 'bank' => 'SECURITY BANK', 'short' => 'SBC CA2',   'acct' => '00000-74790680',      'type' => 'CA', 'cur' => 'PHP', 'bal' => 1496276.67],
            ['gl' => '111200026', 'bank' => 'PBB',           'short' => 'PBB CA',    'acct' => '001-00000-10109',     'type' => 'CA', 'cur' => 'PHP', 'bal' => 8251674.32],
            ['gl' => '111200005', 'bank' => 'PBB',           'short' => 'PBB SA',    'acct' => '010-01-001131-6',     'type' => 'SA', 'cur' => 'PHP', 'bal' => 3203036.20],
            ['gl' => '111200006', 'bank' => 'AUB',           'short' => 'AUB CA',    'acct' => '008-01-000797-1',     'type' => 'CA', 'cur' => 'PHP', 'bal' => 70872.17],
            ['gl' => '111200007', 'bank' => 'METROBANK',     'short' => 'MB CA',     'acct' => '552-7-55200180-6',    'type' => 'CA', 'cur' => 'PHP', 'bal' => 589980.29],
            ['gl' => '111200008', 'bank' => 'PBCOM',         'short' => 'PBCOM CA',  'acct' => '0220-10100-0982',     'type' => 'CA', 'cur' => 'PHP', 'bal' => 65399.58],
            ['gl' => '111200011', 'bank' => 'BPI',           'short' => 'BPI SA',    'acct' => '008103-1540-07',      'type' => 'SA', 'cur' => 'PHP', 'bal' => 30087723.18],
            ['gl' => '111200012', 'bank' => 'BPI',           'short' => 'BPI CA',    'acct' => '008103-1475-31',      'type' => 'CA', 'cur' => 'PHP', 'bal' => 7866372.83],
            ['gl' => '111200022', 'bank' => 'BOC',           'short' => 'BOC CA',    'acct' => '102-00-001948-5',     'type' => 'CA', 'cur' => 'PHP', 'bal' => 272024.60],
            ['gl' => '111200028', 'bank' => 'CHINABANK',     'short' => 'CB CA',     'acct' => '1337-00003094',       'type' => 'CA', 'cur' => 'PHP', 'bal' => 5897943.32],
            ['gl' => '111200032', 'bank' => 'CHINABANK',     'short' => 'CB SA',     'acct' => '1337-02013186',       'type' => 'SA', 'cur' => 'PHP', 'bal' => 12007529.90],

            // DOLLAR ACCOUNTS
            ['gl' => '111200015', 'bank' => 'BDO',           'short' => 'BDO $',     'acct' => '1043-9002-4267',      'type' => 'CA', 'cur' => 'USD', 'bal' => 772.50],
            ['gl' => '111200031', 'bank' => 'BDO',           'short' => 'BDO2 $',    'acct' => '1026-6231-0711',      'type' => 'CA', 'cur' => 'USD', 'bal' => 500.52],
            ['gl' => '111200016', 'bank' => 'UNIONBANK',     'short' => 'UB $',      'acct' => '1322-8001-8960',      'type' => 'CA', 'cur' => 'USD', 'bal' => 1286.92],
            ['gl' => '111200034', 'bank' => 'SECURITY BANK', 'short' => 'SBC $',     'acct' => '0111-0585-48002',     'type' => 'CA', 'cur' => 'USD', 'bal' => 1446.56],
            ['gl' => '111200018', 'bank' => 'PBB',           'short' => 'PBB $',     'acct' => '010-90-000180-6',     'type' => 'CA', 'cur' => 'USD', 'bal' => 956.81],
            ['gl' => '111200019', 'bank' => 'AUB',           'short' => 'AUB $',     'acct' => '008-19-001346-1',     'type' => 'CA', 'cur' => 'USD', 'bal' => 1353.23],
            ['gl' => '111200020', 'bank' => 'METROBANK',     'short' => 'MB $',      'acct' => '552-2-55200047-6',    'type' => 'CA', 'cur' => 'USD', 'bal' => 0.00],
            ['gl' => '111200021', 'bank' => 'PBCOM',         'short' => 'PBCOM $',   'acct' => '0220-2710-00685',     'type' => 'CA', 'cur' => 'USD', 'bal' => 500.00],
            ['gl' => '111200027', 'bank' => 'BPI',           'short' => 'BPI $',     'acct' => '008104-0706-21',      'type' => 'CA', 'cur' => 'USD', 'bal' => 184.18],
        ];

        foreach ($accounts as $a) {
            $gl = GlAccount::where('account_code', $a['gl'])->first();
            if (!$gl) {
                $this->command->warn("GL account {$a['gl']} not found — skipping {$a['short']}");
                continue;
            }

            TreasuryBankAccount::updateOrCreate(
                ['gl_account_id' => $gl->id],
                [
                    'account_number' => $a['acct'],
                    'bank_name'      => $a['bank'],
                    'short_name'     => $a['short'],
                    'currency'       => $a['cur'],
                    'account_type'   => $a['type'],
                    'cash_balance'   => $a['bal'],
                    'balance_as_of'  => $balanceAsOf,
                    'is_active'      => true,
                    'created_by'     => 'system',
                ]
            );
        }

        $this->command->info('Seeded ' . count($accounts) . ' treasury bank accounts with Jan 31, 2026 opening balances.');
    }
}
