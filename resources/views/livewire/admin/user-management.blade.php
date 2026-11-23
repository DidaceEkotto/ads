<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h4 fw-bold text-slate mb-0">{{ __('Gestion des utilisateurs') }}</h1>
        <select wire:model.live="roleFilter" class="form-select form-select-sm w-auto">
            <option value="">Tous les rôles</option>
            <option value="advertiser">Annonceurs</option>
            <option value="publisher">Éditeurs</option>
            <option value="admin">Administrateurs</option>
        </select>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Nom</th><th>Contact</th><th>Rôle</th><th>Solde dispo</th><th>Statut</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td class="fw-semibold">{{ $u->name }}</td>
                            <td class="small text-muted">{{ $u->email }}<br>{{ $u->phone }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $u->role->label() }}</span></td>
                            <td class="fw-semibold">{{ number_format($u->wallet?->available_balance_xaf ?? 0, 0, ',', ' ') }} FCFA</td>
                            <td>
                                <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $u->is_active ? 'Actif' : 'Désactivé' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($u->id !== auth()->id())
                                    <button wire:click="toggleActive({{ $u->id }})" class="btn btn-sm {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $u->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
