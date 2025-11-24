<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PresenceNotificationService;

class SendPresenceCheckNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presence:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer les notifications de présence "Êtes-vous en place?" aux heures configurées';

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
        $this->info('Démarrage de l\'envoi des notifications de présence...');

        try {
            $this->presenceService->sendPresenceCheckNotifications();
            $this->info('✓ Notifications de présence envoyées avec succès');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'envoi des notifications: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
