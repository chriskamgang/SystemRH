<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PresenceNotificationService;

class ProcessExpiredPresenceIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presence:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traiter les incidents de présence expirés (non-réponses après 45 minutes)';

    protected $presenceService;

    public function __construct(PresenceNotificationService $presenceService)
    {
        parent::__construct();
        $this->presenceService = $presenceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $count = $this->presenceService->createIncidentsForNonResponses();

            if ($count > 0) {
                $this->info("✓ Trouvé {$count} incident(s) expiré(s) en attente de validation");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors du traitement des incidents expirés: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
