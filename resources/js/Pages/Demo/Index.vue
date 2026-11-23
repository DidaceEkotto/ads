<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
  accounts: { type: Object, required: true },
  zoneToken: { type: String, default: null },
  socialCode: { type: String, default: null },
  campaignSpentXaf: { type: Number, default: 0 },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function loginAs(role) {
  router.post(`/demo/login/${role}`);
}

function simulateDeposit() {
  router.post('/demo/simulate-deposit', {}, { preserveScroll: true });
}

function simulateFraud() {
  router.post('/demo/simulate-fraud', {}, { preserveScroll: true });
}

onMounted(() => {
  if (props.zoneToken) {
    const script = document.createElement('script');
    script.async = true;
    script.src = `/d/${props.zoneToken}.js`;
    document.body.appendChild(script);
  }
});
</script>

<template>
  <div class="min-vh-100 bg-light">
    <nav class="navbar navbar-dark bg-dark py-3 shadow-sm">
      <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2 text-white fw-bold fs-5">
          <span class="badge bg-warning text-dark">SANDBOX</span>
          <span>CM-Ads — Espace Démo</span>
        </div>
        <Link href="/" class="btn btn-outline-light btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Retour à l'accueil
        </Link>
      </div>
    </nav>

    <main class="container py-5">
      <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>

      <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Console de Démonstration Interactive</h1>
        <p class="text-muted mb-0">
          Testez les 3 espaces (Annonceur, Éditeur, Admin Livewire), la diffusion de publicités et les contrôles anti-fraude sans configuration externe.
        </p>
      </div>

      <!-- Connexion en 1 clic -->
      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary">Annonceur</span>
                <span class="small text-muted">{{ accounts.advertiser.email }}</span>
              </div>
              <div class="fs-4 fw-bold text-primary mb-2">{{ formatXaf(accounts.advertiser.balance_xaf) }}</div>
              <p class="small text-muted mb-4">
                Créez des campagnes, gérez le ciblage et testez le portefeuille XAF.
              </p>
              <button type="button" class="btn btn-primary mt-auto w-100 fw-semibold" @click="loginAs('advertiser')">
                <i class="bi bi-box-arrow-in-right me-1"></i>Entrer en Annonceur
              </button>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success">Éditeur</span>
                <span class="small text-muted">{{ accounts.publisher.email }}</span>
              </div>
              <div class="fs-4 fw-bold text-success mb-1">{{ formatXaf(accounts.publisher.available_xaf) }}</div>
              <div class="small text-muted mb-3">En attente : {{ formatXaf(accounts.publisher.pending_xaf) }}</div>
              <button type="button" class="btn btn-success mt-auto w-100 fw-semibold" @click="loginAs('publisher')">
                <i class="bi bi-box-arrow-in-right me-1"></i>Entrer en Éditeur
              </button>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-dark">Admin Livewire</span>
                <span class="small text-muted">{{ accounts.admin.email }}</span>
              </div>
              <div class="fs-5 fw-bold text-dark mb-2">Back-office Central</div>
              <p class="small text-muted mb-4">
                Validez les sites/campagnes, traitez les retraits MoMo et auditez la fraude.
              </p>
              <button type="button" class="btn btn-dark mt-auto w-100 fw-semibold" @click="loginAs('admin')">
                <i class="bi bi-shield-lock me-1"></i>Entrer en Admin
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Simulateurs temps réel -->
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white py-3 fw-bold">
              <i class="bi bi-window-sidebar text-primary me-2"></i>Simulateur de Site Éditeur (actu-demo.cm)
            </div>
            <div class="card-body">
              <p class="small text-muted">
                L'encart ci-dessous charge en direct le script <code>/d/{{ zoneToken }}.js</code>. Cliquer dessus enregistre un clic CPC (50 FCFA) et crédite l'éditeur de 70 % (35 FCFA) en attente.
              </p>
              <div class="border rounded-3 p-3 bg-light">
                <div class="fw-bold mb-1">Article démo : L'économie numérique au Cameroun</div>
                <p class="small text-muted mb-3">
                  Douala et Yaoundé connaissent une forte croissance des paiements mobiles...
                </p>
                <div v-if="zoneToken" :id="'cm-rec-' + zoneToken" :data-cm-slot="zoneToken"></div>
              </div>
              <div class="small text-muted mt-3">
                Budget déjà dépensé sur cette campagne : <strong>{{ formatXaf(campaignSpentXaf) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white py-3 fw-bold">
              <i class="bi bi-lightning-charge text-warning me-2"></i>Actions Sandbox (Paiements, Social &amp; Fraude)
            </div>
            <div class="card-body d-flex flex-column gap-3">
              <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Simuler une recharge SasPay (+10 000 FCFA)</div>
                  <div class="small text-muted">Crédite le portefeuille Annonceur Démo via LedgerService.</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" @click="simulateDeposit">
                  Créditer
                </button>
              </div>

              <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Simuler un clic Bot (Anti-Fraude)</div>
                  <div class="small text-muted">Envoie un User-Agent <code>curl/8.0.1</code> : bloqué &amp; visible dans l'Admin.</div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" @click="simulateFraud">
                  Simuler Bot
                </button>
              </div>

              <div v-if="socialCode" class="border rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">Tester le lien social <code>/c/{{ socialCode }}</code></div>
                  <div class="small text-muted">Redirection &amp; comptage de clic sur post sponsorisé.</div>
                </div>
                <a :href="'/c/' + socialCode" target="_blank" class="btn btn-outline-success btn-sm fw-semibold">
                  Ouvrir /c/
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
