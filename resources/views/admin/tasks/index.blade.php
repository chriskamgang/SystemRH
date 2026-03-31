@extends('layouts.admin')

@section('title', 'Gestion des Tâches')
@section('page-title', 'Gestion des Tâches')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion des Tâches</h2>
            <p class="text-gray-600 mt-1">Attribuez et suivez les tâches des employés</p>
        </div>
        <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle Tâche
        </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.tasks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Titre de la tâche..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminée</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priorité</label>
                <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Basse</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Moyenne</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Haute</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.tasks.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tâche</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priorité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pénalité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignés</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tasks as $task)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $task->title }}</div>
                        @if($task->description)
                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $task->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($task->priority == 'high')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Haute</span>
                        @elseif($task->priority == 'medium')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Moyenne</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Basse</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($task->status == 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">En attente</span>
                        @elseif($task->status == 'in_progress')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">En cours</span>
                        @elseif($task->status == 'completed')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Terminée</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Annulée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($task->penalty_amount > 0)
                            <span class="text-sm font-semibold text-red-600">{{ number_format($task->penalty_amount, 0, ',', '.') }} FCFA</span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($task->due_date)
                            <span class="{{ $task->due_date->isPast() && $task->status != 'completed' ? 'text-red-600 font-semibold' : '' }}">
                                {{ $task->due_date->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($task->users->take(3) as $user)
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">{{ $user->first_name }}</span>
                            @endforeach
                            @if($task->users->count() > 3)
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">+{{ $task->users->count() - 3 }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            <button onclick="openDetailModal({{ $task->id }})" class="text-green-600 hover:text-green-900" title="Détails & Pénalités">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openEditModal({{ $task->id }})" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteTask({{ $task->id }})" class="text-red-600 hover:text-red-900" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-tasks text-4xl mb-4 block text-gray-300"></i>
                        Aucune tâche trouvée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $tasks->links() }}
        </div>
    </div>
</div>

<!-- Modal Créer/Modifier -->
<div id="taskModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Nouvelle Tâche</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="taskForm">
                <input type="hidden" id="taskId" value="">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                        <input type="text" id="taskTitle" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="taskDescription" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priorité *</label>
                            <select id="taskPriority"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="low">Basse</option>
                                <option value="medium" selected>Moyenne</option>
                                <option value="high">Haute</option>
                            </select>
                        </div>

                        <div id="statusField" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select id="taskStatus"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pending">En attente</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminée</option>
                                <option value="cancelled">Annulée</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Échéance</label>
                            <input type="date" id="taskDueDate"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant pénalité (FCFA)</label>
                            <input type="number" id="taskPenalty" min="0" step="500" placeholder="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Montant à couper si la tâche n'est pas faite</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assigner à *</label>
                        <select id="taskUsers" multiple
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="min-height: 150px;">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->email }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Maintenez Ctrl/Cmd pour sélectionner plusieurs employés</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Détails & Pénalités -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 id="detailTitle" class="text-xl font-bold text-gray-800">Détails de la Tâche</h3>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="detailContent">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Nouvelle Tâche';
        document.getElementById('taskId').value = '';
        document.getElementById('taskTitle').value = '';
        document.getElementById('taskDescription').value = '';
        document.getElementById('taskPriority').value = 'medium';
        document.getElementById('taskDueDate').value = '';
        document.getElementById('taskPenalty').value = '';
        document.getElementById('statusField').classList.add('hidden');

        const select = document.getElementById('taskUsers');
        Array.from(select.options).forEach(opt => opt.selected = false);

        document.getElementById('taskModal').classList.remove('hidden');
    }

    async function openEditModal(taskId) {
        try {
            const response = await fetch(`{{ url('admin/tasks') }}/${taskId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            const task = data.task;

            document.getElementById('modalTitle').textContent = 'Modifier la Tâche';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description || '';
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskStatus').value = task.status;
            document.getElementById('taskDueDate').value = task.due_date ? task.due_date.substring(0, 10) : '';
            document.getElementById('taskPenalty').value = task.penalty_amount || '';
            document.getElementById('statusField').classList.remove('hidden');

            const select = document.getElementById('taskUsers');
            const assignedIds = task.users.map(u => u.id.toString());
            Array.from(select.options).forEach(opt => {
                opt.selected = assignedIds.includes(opt.value);
            });

            document.getElementById('taskModal').classList.remove('hidden');
        } catch (e) {
            alert('Erreur lors du chargement de la tâche.');
        }
    }

    async function openDetailModal(taskId) {
        try {
            const response = await fetch(`{{ url('admin/tasks') }}/${taskId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            const task = data.task;

            const penaltyAmount = task.penalty_amount || 0;
            const formattedPenalty = penaltyAmount > 0 ? penaltyAmount.toLocaleString('fr-FR') + ' FCFA' : 'Aucune';

            let html = `
                <div class="mb-6">
                    <h4 class="text-lg font-semibold">${task.title}</h4>
                    <p class="text-gray-600 mt-1">${task.description || 'Pas de description'}</p>
                    <div class="mt-3 flex gap-3">
                        <span class="text-sm"><strong>Pénalité :</strong> <span class="text-red-600 font-semibold">${formattedPenalty}</span></span>
                        <span class="text-sm"><strong>Échéance :</strong> ${task.due_date ? new Date(task.due_date).toLocaleDateString('fr-FR') : '-'}</span>
                    </div>
                </div>

                <h4 class="text-md font-semibold mb-3">Employés assignés</h4>
                <div class="space-y-3">
            `;

            task.users.forEach(user => {
                const pivot = user.pivot;
                const statusLabels = {pending: 'En attente', in_progress: 'En cours', completed: 'Terminée'};
                const statusColors = {pending: 'gray', in_progress: 'blue', completed: 'green'};
                const statusLabel = statusLabels[pivot.status] || pivot.status;
                const statusColor = statusColors[pivot.status] || 'gray';

                let penaltyHtml = '';
                if (penaltyAmount > 0 && pivot.status !== 'completed') {
                    if (pivot.penalty_approved) {
                        penaltyHtml = `
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Coupure approuvée</span>
                            <button onclick="cancelPenalty(${task.id}, ${user.id})" class="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300">Annuler</button>
                        `;
                    } else {
                        penaltyHtml = `
                            <button onclick="approvePenalty(${task.id}, ${user.id})" class="px-3 py-1 text-xs bg-red-600 text-white rounded-full hover:bg-red-700">
                                Approuver coupure (${penaltyAmount.toLocaleString('fr-FR')} FCFA)
                            </button>
                        `;
                    }
                } else if (pivot.status === 'completed') {
                    penaltyHtml = '<span class="text-xs text-green-600 font-semibold">Tâche terminée</span>';
                }

                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <span class="font-medium">${user.first_name} ${user.last_name}</span>
                            <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">${statusLabel}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            ${penaltyHtml}
                        </div>
                    </div>
                `;
            });

            html += '</div>';

            document.getElementById('detailContent').innerHTML = html;
            document.getElementById('detailTitle').textContent = 'Détails - ' + task.title;
            document.getElementById('detailModal').classList.remove('hidden');
        } catch (e) {
            alert('Erreur lors du chargement.');
        }
    }

    function closeModal() {
        document.getElementById('taskModal').classList.add('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    async function approvePenalty(taskId, userId) {
        if (!confirm('Approuver la coupure pour cet employé ?')) return;

        try {
            const response = await fetch(`{{ url('admin/tasks') }}/${taskId}/approve-penalty/${userId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                openDetailModal(taskId); // Refresh
            }
        } catch (e) {
            alert('Erreur réseau.');
        }
    }

    async function cancelPenalty(taskId, userId) {
        if (!confirm('Annuler la coupure ?')) return;

        try {
            const response = await fetch(`{{ url('admin/tasks') }}/${taskId}/cancel-penalty/${userId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });
            const data = await response.json();
            if (data.success) {
                openDetailModal(taskId); // Refresh
            }
        } catch (e) {
            alert('Erreur réseau.');
        }
    }

    document.getElementById('taskForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const taskId = document.getElementById('taskId').value;
        const isEdit = taskId !== '';

        const selectedUsers = Array.from(document.getElementById('taskUsers').selectedOptions).map(opt => parseInt(opt.value));

        if (selectedUsers.length === 0) {
            alert('Veuillez sélectionner au moins un employé.');
            return;
        }

        const body = {
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDescription').value,
            priority: document.getElementById('taskPriority').value,
            due_date: document.getElementById('taskDueDate').value || null,
            penalty_amount: parseInt(document.getElementById('taskPenalty').value) || 0,
            user_ids: selectedUsers,
        };

        if (isEdit) {
            body.status = document.getElementById('taskStatus').value;
        }

        try {
            const url = isEdit ? `{{ url('admin/tasks') }}/${taskId}` : '{{ route("admin.tasks.store") }}';
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(body),
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de l\'enregistrement.');
            }
        } catch (e) {
            alert('Erreur réseau.');
        }
    });

    async function deleteTask(taskId) {
        if (!confirm('Supprimer cette tâche ?')) return;

        try {
            const response = await fetch(`{{ url('admin/tasks') }}/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (e) {
            alert('Erreur lors de la suppression.');
        }
    }
</script>
@endpush
