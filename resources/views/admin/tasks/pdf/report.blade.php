@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Tâches et Pénalités')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total :</strong> {{ $tasks->count() }}</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Titre</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Date de création</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $index => $task)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $task->title ?? $task->name ?? 'N/A' }}</strong></td>
                <td>{{ \Illuminate\Support\Str::limit($task->description ?? '', 50) }}</td>
                <td>{{ ucfirst($task->status ?? 'N/A') }}</td>
                <td>{{ $task->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
