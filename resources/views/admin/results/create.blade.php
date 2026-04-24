@extends('layouts.admin')

@section('title', 'Nouveau Résultat')
@section('page-title', 'Publier un Résultat')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-8 text-gray-800">
        <form method="POST" action="{{ route('admin.results.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sélectionner l'Étudiant *</label>
                    <select name="user_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Choisir un étudiant --</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->employee_id }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Titre du Résultat *</label>
                        <input type="text" name="title" required placeholder="Ex: CC Programmation Mobile" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Type *</label>
                        <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="CC">Contrôle Continu (CC)</option>
                            <option value="Exam">Examen</option>
                            <option value="Semester">Résultat Semestriel</option>
                            <option value="Rattrapage">Rattrapage</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Semestre</label>
                        <select name="semester" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">N/A</option>
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Année Académique</label>
                        <input type="text" name="academic_year" placeholder="Ex: 2025-2026" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Fichier (PDF ou Image) *</label>
                    <input type="file" name="file" required accept=".pdf,image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-gray-50">
                    <p class="text-xs text-gray-400 mt-1 italic">Maximum 5 Mo</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description / Notes</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Information supplémentaire optionnelle..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-8 border-t mt-8">
                <a href="{{ route('admin.results.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Annuler
                </a>
                <button type="submit" class="px-8 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-md">
                    Publier le Résultat
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
