@extends('layouts.admin')

@section('title', 'Assigner un congé')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">

    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.leaves.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Assigner un congé</h1>
            <p class="text-sm text-gray-500 mt-0.5">Le congé sera directement approuvé — la biométrie en tiendra compte automatiquement</p>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Info box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex gap-3">
        <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
        <div class="text-sm text-blue-800">
            <strong>Comment ça fonctionne :</strong><br>
            Une fois le congé assigné, l'employé <strong>ne pourra pas faire de check-in</strong> pendant la période de congé.
            Il ne recevra pas non plus de <strong>notifications de présence</strong>. Le congé est immédiatement approuvé sans demande préalable de l'employé.
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.leaves.assign.store') }}" method="POST">
            @csrf

            <div class="space-y-5">

                <!-- Employé -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Employé <span class="text-red-500">*</span>
                    </label>
                    <select name="user_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('user_id') border-red-400 @enderror">
                        <option value="">— Sélectionner un employé —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ (old('user_id', $selectedUserId) == $user->id) ? 'selected' : '' }}>
                                {{ $user->full_name }}
                                @if($user->employee_id) ({{ $user->employee_id }}) @endif
                                — {{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type de congé -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Type de congé <span class="text-red-500">*</span>
                    </label>
                    <select name="type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-400 @enderror">
                        <option value="">— Sélectionner le type —</option>
                        @foreach(\App\Models\LeaveRequest::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_date') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_date') border-red-400 @enderror">
                    </div>
                </div>

                <!-- Calcul automatique des jours -->
                <div id="days-preview" class="hidden bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-800">
                    <i class="fas fa-calendar-check mr-2"></i>
                    <span id="days-count"></span>
                </div>

                <!-- Motif -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motif / Remarque</label>
                    <textarea name="reason" rows="3"
                              placeholder="Motif du congé (optionnel)..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('reason') }}</textarea>
                </div>

            </div>

            <div class="flex justify-end gap-3 mt-8 pt-5 border-t border-gray-100">
                <a href="{{ route('admin.leaves.index') }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-check mr-2"></i> Assigner le congé
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const startInput = document.querySelector('[name="start_date"]');
    const endInput = document.querySelector('[name="end_date"]');
    const preview = document.getElementById('days-preview');
    const daysCount = document.getElementById('days-count');

    function updateDays() {
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        if (startInput.value && endInput.value && end >= start) {
            const diff = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
            daysCount.textContent = diff + ' jour(s) de congé du '
                + start.toLocaleDateString('fr-FR') + ' au '
                + end.toLocaleDateString('fr-FR');
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    startInput.addEventListener('change', updateDays);
    endInput.addEventListener('change', updateDays);
</script>
@endpush
@endsection
