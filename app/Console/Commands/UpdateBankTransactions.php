<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Services\GoCardlessDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpdateBankTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank-transactions:update {--user-id= : ID de l\'utilisateur spécifique (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour toutes les transactions bancaires de tous les utilisateurs depuis GoCardless';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Mise à jour des transactions bancaires...');

        $userId = $this->option('user-id');

        // Récupérer les comptes bancaires
        $query = BankAccount::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $bankAccounts = $query->whereNotNull('gocardless_account_id')->get();

        if ($bankAccounts->isEmpty()) {
            $this->warn('Aucun compte bancaire avec un ID GoCardless trouvé.');

            return Command::SUCCESS;
        }

        $this->info("📊 {$bankAccounts->count()} compte(s) bancaire(s) trouvé(s).");

        $successCount = 0;
        $errorCount = 0;

        foreach ($bankAccounts as $account) {
            try {
                // Authentifier l'utilisateur du compte pour le service GoCardless
                if (!$account->user) {
                    $this->error("❌ Utilisateur non trouvé pour le compte {$account->name}");
                    $errorCount++;
                    continue;
                }

                /** @var \App\Models\User $user */
                $user = $account->user;
                Auth::login($user);
                $goCardlessService = new GoCardlessDataService;

                // Mettre à jour les transactions du compte
                $responses = $account->updateFromGocardless($goCardlessService);

                if ($responses && isset($responses['transactions'])) {
                    $transactionResponse = $responses['transactions'];

                    if (isset($transactionResponse['status']) && $transactionResponse['status'] === 'error') {
                        $this->error("❌ Erreur pour le compte {$account->name}: {$transactionResponse['message']}");
                        $errorCount++;
                    } else {
                        $this->info("✅ Compte {$account->name}: {$transactionResponse['message']}");
                        $successCount++;
                    }
                } else {
                    $this->warn("⚠️ Aucune réponse pour le compte {$account->name}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur lors de la mise à jour du compte {$account->name}: " . $e->getMessage());
                Log::error("Erreur mise à jour transactions compte bancaire {$account->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Résumé
        $this->newLine();
        $this->info('📈 Résumé de la mise à jour des transactions:');
        $this->info("✅ Comptes mis à jour avec succès: {$successCount}");
        $this->info("❌ Comptes en erreur: {$errorCount}");
        $this->info("📊 Total traité: {$bankAccounts->count()}");

        if ($errorCount > 0) {
            $this->warn('⚠️ Certains comptes ont rencontré des erreurs. Vérifiez les logs pour plus de détails.');
        }

        return Command::SUCCESS;
    }
}
