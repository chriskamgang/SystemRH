<?php

namespace App\Console\Commands;

use App\Models\JustificationRequest;
use App\Models\PayrollJustification;
use App\Models\Tardiness;
use Illuminate\Console\Command;

class FixApprovedJustifications extends Command
{
    protected $signature = 'fix:approved-justifications';
    protected $description = 'Creer les PayrollJustification manquantes pour les justifications de retard deja approuvees';

    public function handle()
    {
        $approved = JustificationRequest::where('status', 'approved')
            ->where('type', 'tardiness')
            ->get();

        if ($approved->isEmpty()) {
            // Essayer aussi avec type = 'retard' ou autre
            $approved = JustificationRequest::where('status', 'approved')
                ->where('type', '!=', 'absence')
                ->get();
        }

        $this->info("Justifications approuvees trouvees: {$approved->count()}");

        $created = 0;
        $skipped = 0;

        foreach ($approved as $justification) {
            $tardiness = Tardiness::where('user_id', $justification->user_id)
                ->whereDate('date', $justification->date)
                ->get();

            $totalLateMinutes = (int) $tardiness->sum('late_minutes');

            if ($totalLateMinutes <= 0) {
                $this->warn("  - {$justification->user->first_name} {$justification->user->last_name} ({$justification->date->format('d/m/Y')}): aucun retard trouve, skip");
                $skipped++;
                continue;
            }

            $year = $justification->date->year;
            $month = $justification->date->month;

            $existing = PayrollJustification::where('user_id', $justification->user_id)
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'approved')
                ->first();

            if ($existing) {
                // Verifier si les minutes sont deja comptees
                if ($existing->late_minutes_justified >= $totalLateMinutes) {
                    $this->line("  - {$justification->user->first_name} {$justification->user->last_name} ({$justification->date->format('d/m/Y')}): deja corrige, skip");
                    $skipped++;
                    continue;
                }

                $existing->update([
                    'late_minutes_justified' => $existing->late_minutes_justified + $totalLateMinutes,
                    'reason' => $existing->reason . ' | ' . $justification->reason,
                ]);
                $this->info("  + {$justification->user->first_name} {$justification->user->last_name} ({$justification->date->format('d/m/Y')}): mis a jour (+{$totalLateMinutes} min)");
            } else {
                PayrollJustification::create([
                    'user_id' => $justification->user_id,
                    'created_by' => $justification->reviewed_by ?? 1,
                    'year' => $year,
                    'month' => $month,
                    'days_justified' => 0,
                    'late_minutes_justified' => $totalLateMinutes,
                    'reason' => $justification->reason ?? 'Justification approuvee (correction automatique)',
                    'status' => 'approved',
                ]);
                $this->info("  + {$justification->user->first_name} {$justification->user->last_name} ({$justification->date->format('d/m/Y')}): cree ({$totalLateMinutes} min justifiees)");
            }

            $created++;
        }

        $this->newLine();
        $this->info("Termine: {$created} corrigee(s), {$skipped} ignoree(s).");

        return Command::SUCCESS;
    }
}
