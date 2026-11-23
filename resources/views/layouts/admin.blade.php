<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Administration — CM-Ads' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
        .bg-slate { background-color: #192733 !important; }
        .text-slate { color: #192733 !important; }
        .text-coral { color: #ff5c35 !important; }
        .bg-coral { background-color: #ff5c35 !important; }
        .bg-coral-subtle { background-color: #fff1ed !important; }
        .btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
        .btn-coral:hover { background-color: #e64922; border-color: #e64922; color: #fff; }
        .adm-sidebar { width: 255px; }
        .hs-logo-mark {
            width: 32px; height: 32px; border-radius: 8px;
            background-color: #ff5c35; color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .adm-nav-link {
            color: rgba(255, 255, 255, 0.78);
            border-radius: 0.6rem;
            padding: 0.65rem 0.9rem;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .adm-nav-link:hover { color: #fff; background-color: rgba(255, 255, 255, 0.08); }
        .adm-nav-link.active { background-color: #ff5c35 !important; color: #fff !important; }
        .avatar-initials {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #ff5c35, #192733);
            color: #fff; font-weight: 700; font-size: 0.9rem;
            display: inline-flex; align-items: center; justify-content: center;
        }
    </style>
    @livewireStyles
</head>
<body class="bg-light">
    @php
        $adminUser = auth()->user();
        $siteLogo = \App\Models\Setting::getValue('site_logo_path', '');
        $siteName = \App\Models\Setting::getValue('site_name', 'CM-Ads');
    @endphp

    <div class="d-flex min-vh-100">
        <!-- Sidebar Desktop -->
        <aside class="adm-sidebar bg-slate text-white d-none d-lg-flex flex-column flex-shrink-0 p-3">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-white text-decoration-none fw-bold fs-4 mb-4 px-2">
                @if($siteLogo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) }}" alt="{{ $siteName }}" class="me-2 rounded" style="max-height: 34px; object-fit: contain;">
                @else
                    <span class="hs-logo-mark me-2"><i class="bi bi-shield-lock-fill"></i></span>
                    {{ $siteName }}
                @endif
            </a>

            <div class="small text-uppercase text-white-50 fw-semibold px-2 mb-2">Administration</div>
            <ul class="nav nav-pills flex-column gap-1 mb-auto">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i>{{ __('Vue globale') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.validations') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.validations') ? 'active' : '' }}">
                        <i class="bi bi-check2-square me-2"></i>{{ __('Validations') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.payouts') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.payouts') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack me-2"></i>{{ __('Retraits MoMo / OM') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.fraud') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.fraud') ? 'active' : '' }}">
                        <i class="bi bi-bug me-2"></i>{{ __('Anti-fraude') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i>{{ __('Utilisateurs') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                        <i class="bi bi-gear me-2"></i>{{ __('Paramètres') }}
                    </a>
                </li>
            </ul>

            <div class="border-top border-secondary border-opacity-25 pt-3 mt-3 small text-white-50 px-2">
                CM-Ads Admin • Livewire
            </div>
        </aside>

        <!-- Offcanvas Sidebar Mobile -->
        <div id="admMobileSidebar" class="offcanvas offcanvas-start bg-slate text-white" tabindex="-1">
            <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
                <span class="fw-bold fs-5">CM<span class="text-coral">-Ads</span> Admin</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="nav nav-pills flex-column gap-1">
                    <li><a href="{{ route('admin.dashboard') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2 me-2"></i>{{ __('Vue globale') }}</a></li>
                    <li><a href="{{ route('admin.validations') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.validations') ? 'active' : '' }}"><i class="bi bi-check2-square me-2"></i>{{ __('Validations') }}</a></li>
                    <li><a href="{{ route('admin.payouts') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.payouts') ? 'active' : '' }}"><i class="bi bi-cash-stack me-2"></i>{{ __('Retraits MoMo / OM') }}</a></li>
                    <li><a href="{{ route('admin.fraud') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.fraud') ? 'active' : '' }}"><i class="bi bi-bug me-2"></i>{{ __('Anti-fraude') }}</a></li>
                    <li><a href="{{ route('admin.users') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>{{ __('Utilisateurs') }}</a></li>
                    <li><a href="{{ route('admin.settings') }}" class="nav-link adm-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"><i class="bi bi-gear me-2"></i>{{ __('Paramètres') }}</a></li>
                </ul>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="flex-grow-1 d-flex flex-column min-w-0">
            <header class="navbar navbar-light bg-white border-bottom sticky-top px-3 px-lg-4 py-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light border d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#admMobileSidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <span class="fw-semibold text-slate d-none d-sm-inline">CM-Ads Admin</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn p-0 border-0 rounded-circle d-flex align-items-center justify-content-center"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            @if($adminUser?->avatarUrl())
                                <img src="{{ $adminUser->avatarUrl() }}" alt="{{ $adminUser->name }}" class="rounded-circle object-fit-cover border" style="width:40px;height:40px;">
                            @else
                                <span class="avatar-initials">{{ $adminUser?->initials() ?? 'AD' }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="min-width: 220px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-slate text-truncate">{{ $adminUser?->name }}</div>
                                <div class="small text-muted text-truncate">{{ $adminUser?->email }}</div>
                            </li>
                            <li><a class="dropdown-item py-2" href="{{ route('admin.settings') }}"><i class="bi bi-gear me-2 text-muted"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button" class="dropdown-item py-2 text-danger fw-medium" data-bs-toggle="modal" data-bs-target="#adminLogoutModal">
                                    <i class="bi bi-box-arrow-right me-2"></i>{{ __('Déconnexion') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="container-fluid p-3 p-lg-4 flex-grow-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Modal statique de confirmation de déconnexion -->
    <div class="modal fade" id="adminLogoutModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-slate">Confirmer la déconnexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-muted">
                    Voulez-vous vraiment vous déconnecter de l'administration ?
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger fw-semibold">
                            <i class="bi bi-box-arrow-right me-1"></i>Se déconnecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
