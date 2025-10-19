<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Services\GoCardlessDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpdateBankAccountsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank-accounts:update-data {--user-id= : ID de l\'utilisateur spécifique (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour toutes les données des comptes bancaires depuis GoCardless (sauf les noms)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Mise à jour des données des comptes bancaires...');

        $userId = $this->option('user-id');

        // Récupérer les comptes bancaires
        $query = BankAccount::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $bankAccounts = $query->whereNotNull('gocardless_account_id')->get();

        if ($bankAccounts->isEmpty()) {
            $this->warn('Aucun compte bancaire avec un ID GoCardless trouvé.');

            return;
        }

        $this->info("📊 {$bankAccounts->count()} compte(s) bancaire(s) trouvé(s).");

        $successCount = 0;
        $errorCount = 0;

        foreach ($bankAccounts as $account) {
            $this->line("🔄 Traitement du compte: {$account->name} (ID: {$account->gocardless_account_id})");

            try {
                // Authentifier l'utilisateur du compte pour le service GoCardless
                if (! $account->user) {
                    $this->error("❌ Utilisateur non trouvé pour le compte {$account->name}");
                    $errorCount++;

                    continue;
                }
                /** @var \App\Models\User $user */
                $user = $account->user;
                Auth::login($user);
                $goCardlessService = new GoCardlessDataService;

                // Récupérer les détails du compte directement depuis GoCardless (sans cache)
                $accountDetails = $goCardlessService->getAccountDetailsDirect($account->gocardless_account_id);

                if (isset($accountDetails['status_code']) && $accountDetails['status_code'] !== 200) {
                    $this->error("❌ Erreur lors de la récupération des détails du compte {$account->name}: ".json_encode($accountDetails));
                    $errorCount++;

                    continue;
                }

                // Récupérer les détails de l'accord directement depuis GoCardless (sans cache)
                $agreementDetails = null;
                if ($account->agreement_id) {
                    $agreementDetails = $goCardlessService->getAgreementDetailsDirect($account->agreement_id);
                }

                // Afficher les données récupérées depuis GoCardless
                $this->line('📋 Données récupérées depuis GoCardless:');
                if (isset($accountDetails['account'])) {
                    $this->line('   - IBAN: '.($accountDetails['account']['iban'] ?? 'N/A'));
                    $this->line('   - Devise: '.($accountDetails['account']['currency'] ?? 'N/A'));
                    $this->line('   - Titulaire: '.($accountDetails['account']['name'] ?? $accountDetails['account']['ownerName'] ?? 'N/A'));
                    $this->line('   - Type: '.($accountDetails['account']['cashAccountType'] ?? 'N/A'));
                }

                if ($agreementDetails) {
                    $this->line("📅 Détails de l'accord:");
                    $this->line('   - Validité: '.($agreementDetails['access_valid_for_days'] ?? 'N/A').' jours');
                    if (isset($agreementDetails['access_valid_for_days'])) {
                        $newDate = now()->addDays($agreementDetails['access_valid_for_days']);
                        $this->line('   - Nouvelle date limite: '.$newDate->format('d/m/Y'));
                    }
                }

                // Mettre à jour les données du compte (sauf le nom)
                $updateData = [];

                // Mettre à jour l'IBAN
                if (isset($accountDetails['account']['iban'])) {
                    $updateData['iban'] = $accountDetails['account']['iban'];
                }

                // Mettre à jour la devise
                if (isset($accountDetails['account']['currency'])) {
                    $updateData['currency'] = $accountDetails['account']['currency'];
                }

                // Mettre à jour le nom du titulaire
                if (isset($accountDetails['account']['name'])) {
                    $updateData['owner_name'] = $accountDetails['account']['name'];
                } elseif (isset($accountDetails['account']['ownerName'])) {
                    $updateData['owner_name'] = $accountDetails['account']['ownerName'];
                }

                // Mettre à jour le type de compte
                if (isset($accountDetails['account']['cashAccountType'])) {
                    $updateData['cash_account_type'] = $accountDetails['account']['cashAccountType'];
                }

                // Mettre à jour la date limite de validité
                if ($agreementDetails && isset($agreementDetails['access_valid_for_days'])) {
                    $updateData['end_valid_access'] = now()->addDays($agreementDetails['access_valid_for_days']);
                }

                // Sauvegarder les modifications
                if (! empty($updateData)) {
                    $account->update($updateData);

                    $updatedFields = array_keys($updateData);
                    $this->info("✅ Compte {$account->name} mis à jour: ".implode(', ', $updatedFields));
                    $successCount++;
                } else {
                    $this->warn("⚠️  Aucune donnée à mettre à jour pour le compte {$account->name}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur lors de la mise à jour du compte {$account->name}: ".$e->getMessage());
                Log::error("Erreur mise à jour compte bancaire {$account->id}: ".$e->getMessage());
                $errorCount++;
            }
        }

        // Résumé
        $this->newLine();
        $this->info('📈 Résumé de la mise à jour:');
        $this->info("✅ Comptes mis à jour avec succès: {$successCount}");
        $this->info("❌ Comptes en erreur: {$errorCount}");
        $this->info("📊 Total traité: {$bankAccounts->count()}");

        if ($errorCount > 0) {
            $this->warn('⚠️  Certains comptes ont rencontré des erreurs. Vérifiez les logs pour plus de détails.');
        }

        return Command::SUCCESS;
    }
}
