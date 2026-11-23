<div>
    <h1 class="h4 fw-bold mb-4">{{ __('Moniteur Anti-Fraude') }}</h1>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Motif</th><th>Score</th><th>IP</th><th>Éditeur</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($flags as $flag)
                        <tr>
                            <td class="small">{{ $flag->created_at?->format('d/m H:i') }}</td>
                            <td><span class="badge bg-danger">{{ $flag->reason->label() }}</span></td>
                            <td>{{ $flag->score }}/100</td>
                            <td class="small"><code>{{ $flag->ip_address }}</code></td>
                            <td class="small">{{ $flag->publisher?->name }}</td>
                            <td>
                                @if(!$flag->is_resolved)
                                    <button wire:click="resolve({{ $flag->id }})" class="btn btn-outline-secondary btn-sm">Résoudre</button>
                                @else
                                    <span class="text-success small">Traité</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune alerte de fraude.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
