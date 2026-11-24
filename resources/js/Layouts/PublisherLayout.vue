<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import { useTranslation } from '../i18n/useTranslation';

const { t } = useTranslation();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const supportWhatsapp = computed(() => page.props.supportWhatsapp);
const siteSettings = computed(() => page.props.siteSettings || {});
const currentUrl = computed(() => page.url);
const showLogoutModal = ref(false);

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function isActive(prefix) {
  return currentUrl.value.startsWith(prefix);
}

function confirmLogout() {
  showLogoutModal.value = false;
  router.post('/logout');
}
</script>

<template>
  <div class="d-flex min-vh-100 bg-light">
    <!-- Sidebar Desktop -->
    <aside class="pub-sidebar bg-slate text-white d-none d-lg-flex flex-column flex-shrink-0 p-3">
      <Link href="/publisher/dashboard" class="d-flex align-items-center text-white text-decoration-none fw-bold fs-4 mb-4 px-2">
        <img v-if="siteSettings.logo_url" :src="siteSettings.logo_url" :alt="siteSettings.name" class="me-2 rounded" style="max-height: 34px; object-fit: contain;" />
        <template v-else>
          <span class="hs-logo-mark me-2"><i class="bi bi-globe2"></i></span>
          CM<span class="text-coral">-Ads</span>
        </template>
      </Link>

      <div class="small text-uppercase text-white-50 fw-semibold px-2 mb-2">Espace Éditeur</div>
      <ul class="nav nav-pills flex-column gap-1 mb-auto">
        <li class="nav-item">
          <Link href="/publisher/dashboard" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/dashboard') }">
            <i class="bi bi-speedometer2 me-2"></i>{{ t('dashboard') }}
          </Link>
        </li>
        <li class="nav-item">
          <Link href="/publisher/stats" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/stats') }">
            <i class="bi bi-graph-up-arrow me-2"></i>Revenus &amp; Stats
          </Link>
        </li>
        <li class="nav-item">
          <Link href="/publisher/sites" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/sites') }">
            <i class="bi bi-globe2 me-2"></i>Mes Sites
          </Link>
        </li>
        <li class="nav-item">
          <Link href="/publisher/social" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/social') }">
            <i class="bi bi-share me-2"></i>Réseaux Sociaux
          </Link>
        </li>
        <li class="nav-item">
          <Link href="/publisher/payouts" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/payouts') }">
            <i class="bi bi-cash-stack me-2"></i>Retraits
          </Link>
        </li>
        <li class="nav-item">
          <Link href="/publisher/payment-methods" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/payment-methods') }">
            <i class="bi bi-phone me-2"></i>Mobile Money
          </Link>
        </li>
      </ul>

      <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
        <a :href="supportWhatsapp" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm w-100">
          <i class="bi bi-whatsapp me-1"></i>{{ t('support_whatsapp') }}
        </a>
      </div>
    </aside>

    <!-- Offcanvas Sidebar Mobile -->
    <div id="pubMobileSidebar" class="offcanvas offcanvas-start bg-slate text-white" tabindex="-1">
      <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <span class="fw-bold fs-5">CM<span class="text-coral">-Ads</span></span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column">
        <ul class="nav nav-pills flex-column gap-1 mb-auto">
          <li><Link href="/publisher/dashboard" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/dashboard') }"><i class="bi bi-speedometer2 me-2"></i>{{ t('dashboard') }}</Link></li>
          <li><Link href="/publisher/stats" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/stats') }"><i class="bi bi-graph-up-arrow me-2"></i>Revenus &amp; Stats</Link></li>
          <li><Link href="/publisher/sites" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/sites') }"><i class="bi bi-globe2 me-2"></i>Mes Sites</Link></li>
          <li><Link href="/publisher/social" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/social') }"><i class="bi bi-share me-2"></i>Réseaux Sociaux</Link></li>
          <li><Link href="/publisher/payouts" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/payouts') }"><i class="bi bi-cash-stack me-2"></i>Retraits</Link></li>
          <li><Link href="/publisher/payment-methods" class="nav-link pub-nav-link" :class="{ active: isActive('/publisher/payment-methods') }"><i class="bi bi-phone me-2"></i>Mobile Money</Link></li>
        </ul>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="flex-grow-1 d-flex flex-column min-w-0">
      <header class="navbar navbar-light bg-white border-bottom sticky-top px-3 px-lg-4 py-2 shadow-sm">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-light border d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#pubMobileSidebar">
            <i class="bi bi-list fs-5"></i>
          </button>
          <span class="fw-semibold text-slate d-none d-sm-inline">Éditeur</span>
        </div>

        <div class="d-flex align-items-center gap-2 gap-md-3">
          <Link href="/publisher/payouts" class="badge bg-light text-dark border text-decoration-none py-2 px-3 rounded-pill">
            <i class="bi bi-wallet2 text-coral me-1"></i>
            <span class="d-none d-md-inline">Dispo : </span>
            <strong>{{ formatXaf(user?.wallet?.available_balance_xaf) }}</strong>
          </Link>
          <span class="badge bg-coral-subtle text-coral border border-coral py-2 px-3 rounded-pill d-none d-sm-inline-block">
            {{ t('pending_balance') }} : <strong>{{ formatXaf(user?.wallet?.pending_balance_xaf) }}</strong>
          </span>

          <LocaleSwitcher />

          <!-- Dropdown Avatar / Initiales -->
          <div class="dropdown">
            <button
              type="button"
              class="btn p-0 border-0 rounded-circle d-flex align-items-center justify-content-center avatar-btn"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              :title="user?.name"
            >
              <img
                v-if="user?.avatar_url"
                :src="user.avatar_url"
                :alt="user?.name"
                class="rounded-circle object-fit-cover w-100 h-100 border"
              />
              <span v-else class="avatar-initials">
                {{ user?.initials || 'E' }}
              </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="min-width: 220px;">
              <li class="px-3 py-2 border-bottom">
                <div class="fw-bold text-slate text-truncate">{{ user?.name }}</div>
                <div class="small text-muted text-truncate">{{ user?.email }}</div>
              </li>
              <li>
                <Link class="dropdown-item py-2" href="/publisher/profile">
                  <i class="bi bi-person me-2 text-muted"></i>Mon profil
                </Link>
              </li>
              <li>
                <Link class="dropdown-item py-2" href="/publisher/password">
                  <i class="bi bi-shield-lock me-2 text-muted"></i>Mot de passe
                </Link>
              </li>
              <li>
                <Link class="dropdown-item py-2" href="/publisher/payment-methods">
                  <i class="bi bi-phone me-2 text-muted"></i>Mobile Money
                </Link>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <button type="button" class="dropdown-item py-2 text-danger fw-medium" @click="showLogoutModal = true">
                  <i class="bi bi-box-arrow-right me-2"></i>{{ t('logout') }}
                </button>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <main class="container-fluid p-3 p-lg-4 flex-grow-1">
        <slot />
      </main>
    </div>

    <!-- Modal statique de confirmation de déconnexion -->
    <div v-if="showLogoutModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(17, 28, 38, 0.55);" @click.self.stop>
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-slate">Confirmer la déconnexion</h5>
            <button type="button" class="btn-close" @click="showLogoutModal = false"></button>
          </div>
          <div class="modal-body text-muted">
            Voulez-vous vraiment vous déconnecter de votre espace Éditeur ?
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light border" @click="showLogoutModal = false">Annuler</button>
            <button type="button" class="btn btn-danger fw-semibold" @click="confirmLogout">
              <i class="bi bi-box-arrow-right me-1"></i>Se déconnecter
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg-slate { background-color: #192733 !important; }
.text-slate { color: #192733 !important; }
.text-coral { color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }
.border-coral { border-color: #ff5c35 !important; }

.pub-sidebar { width: 250px; }

.hs-logo-mark {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background-color: #ff5c35;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
}

.pub-nav-link {
  color: rgba(255, 255, 255, 0.78);
  border-radius: 0.6rem;
  padding: 0.65rem 0.9rem;
  font-weight: 500;
  transition: background-color 0.2s ease, color 0.2s ease;
}
.pub-nav-link:hover {
  color: #fff;
  background-color: rgba(255, 255, 255, 0.08);
}
.pub-nav-link.active {
  background-color: #ff5c35 !important;
  color: #fff !important;
}

.avatar-btn { width: 40px; height: 40px; }
.avatar-initials {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ff5c35, #192733);
  color: #fff;
  font-weight: 700;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>
