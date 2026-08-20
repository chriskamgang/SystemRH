@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport de Pointages')
@section('subtitle', $periodLabel)

@section('meta-info')
    <p><strong>Periode :</strong> {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</p>
    <p><strong>Type :</strong> {{ $period === 'day' ? 'Journalier' : ($period === 'week' ? 'Hebdomadaire' : 'Mensuel') }}</p>
    <p><strong>Employes :</strong> {{ $report->count() }} | <strong>Total heures :</strong> {{ intdiv($globalMinutes, 60) }}h {{ str_pad($globalMinutes % 60, 2, '0', STR_PAD_LEFT) }} | <strong>Retards :</strong> {{ $globalLate }}</p>
@endsection

@section('content')
    @foreach($report as $entry)
    <div style="margin-bottom: 15px;">
        <table>
            <thead>
                <tr>
                    <th colspan="7" style="background: #374151; text-align: left; padding: 6px 8px;">
                        {{ $entry->user->full_name }}
                        <span style="float: right; font-weight: normal;">
                            Total: {{ $entry->total_hours }} | {{ $entry->days_worked }} jour(s) | Moy: {{ $entry->avg_hours }} | Retards: {{ $entry->total_late }}
                        </span>
                    </th>
                </tr>
                <tr>
                    <th>Date</th>
                    <th>Campus</th>
                    <th class="text-center">Entree</th>
                    <th class="text-center">Sortie</th>
                    <th class="text-center">Duree session</th>
                    <th class="text-center">Total jour</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->days as $day)
                    @foreach($day['sessions'] as $sIndex => $session)
                    <tr>
                        @if($sIndex === 0)
                        <td rowspan="{{ count($day['sessions']) }}">
                            <strong>{{ $day['date']->format('d/m/Y') }}</strong><br>
                            <span style="font-size: 8px; color: #666;">{{ $day['date']->locale('fr')->isoFormat('dddd') }}</span>
                        </td>
                        @endif

                        <td>
                            {{ $session->campus ? $session->campus->name : '-' }}
                            @if($session->ue)
                            <br><span style="font-size: 8px; color: #2563eb;">{{ $session->ue->code_ue }}</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($session->check_in)
                                <span class="{{ $session->check_in->is_late ? 'text-orange' : 'text-green' }}">
                                    {{ $session->check_in->timestamp->format('H:i') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>

                        <td class="text-center">
                            @if($session->check_out)
                                {{ $session->check_out->timestamp->format('H:i') }}
                            @else
                                <span class="text-orange">-</span>
                            @endif
                        </td>

                        <td class="text-center">{{ $session->minutes > 0 ? $session->duration : '-' }}</td>

                        @if($sIndex === 0)
                        <td class="text-center font-bold" rowspan="{{ count($day['sessions']) }}">
                            {{ $day['worked_minutes'] > 0 ? $day['worked_hours'] : '-' }}
                        </td>
                        <td class="text-center" rowspan="{{ count($day['sessions']) }}">
                            @if($day['is_late'])
                                <span class="text-red">Retard {{ $day['late_minutes'] }}min</span>
                            @else
                                <span class="text-green">OK</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                @endforeach
                <tr style="background: #eff6ff;">
                    <td colspan="4" class="text-right font-bold" style="color: #1e40af;">TOTAL</td>
                    <td class="text-center font-bold" style="color: #1e40af;">{{ $entry->total_hours }}</td>
                    <td class="text-center" style="color: #1e40af;">{{ $entry->days_worked }} jour(s)</td>
                    <td class="text-center" style="color: #1e40af;">Moy: {{ $entry->avg_hours }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    @if($report->isEmpty())
    <p style="text-align: center; color: #999; margin-top: 30px;">Aucun pointage pour cette periode.</p>
    @endif
@endsection
