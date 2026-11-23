<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

const props = defineProps({
  accounts: { type: Array, required: true },
  availableCampaigns: { type: Array, required: true },
  placements: { type: Array, required: true },
  platforms: { type: Array, required: true },
  minFollowers: { type: Number, required: true },
  minPostHours: { type: Number, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const approvedAccounts = computed(() => props.accounts.filter((a) => a.is_approved));
const selectedAccountId = ref(approvedAccounts.value[0]?.id ?? '');
const showAddModal = ref(false);
const copiedKey = ref(null);
const proofForms = ref({});

const accountForm = useForm({
  platform: 'facebook',
  handle: '',
  profile_url: 'https://',
});

function addAccount() {
  accountForm.post('/publisher/social/accounts', {
    onSuccess: () => {
      accountForm.reset('handle');
      showAddModal.value = false;
    },
  });
}

function joinCampaign(campaignId) {
  router.post(`/publisher/social/campaigns/${campaignId}/join`, {
    social_account_id: selectedAccountId.value,
  }, { preserveScroll: true });
}

function getProofState(placementId) {
  if (!proofForms.value[placementId]) {
    proofForms.value[placementId] = { post_url: '', has_disclosure_tag: false };
  }
  return proofForms.value[placementId];
}

function submitProof(placementId) {
  router.post(`/publisher/social/placements/${placementId}/proof`, getProofState(placementId), {
    preserveScroll: true,
  });
}

async function copyText(text, key) {
  await navigator.clipboard.writeText(text);
  copiedKey.value = key;
  setTimeout(() => { copiedKey.value = null; }, 2000);
}
</script>

<template>
  <PublisherLayout>
    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <div v-if="flashError" class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ flashError }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- En-tête simple -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">Posts Sociaux Sponsorisés</h1>
        <p class="text-muted small mb-0">
          1. Ajoutez votre page • 2. Copiez le texte + lien <code>/c/</code> • 3. Collez le lien de votre post publié.
        </p>
      </div>
      <button type="button" class="btn btn-coral fw-semibold" @click="showAddModal = true">
        <i class="bi bi-plus-lg me-1"></i>Ajouter un compte social
      </button>
    </div>

    <!-- Mes comptes sociaux (bandeau compact) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
          <span class="fw-bold small text-slate text-uppercase">Mes comptes ({{ accounts.length }})</span>
          <div v-if="approvedAccounts.length > 1" class="d-flex align-items-center gap-2">
            <span class="small text-muted">Publier avec :</span>
            <select v-model="selectedAccountId" class="form-select form-select-sm w-auto">
              <option v-for="a in approvedAccounts" :key="a.id" :value="a.id">
                {{ a.platform_label }} (@{{ a.handle }})
              </option>
            </select>
          </div>
        </div>

        <div v-if="accounts.length" class="d-flex flex-wrap gap-2">
          <div
            v-for="a in accounts"
            :key="a.id"
            class="border rounded-3 px-3 py-2 bg-light d-flex align-items-center gap-2 small"
          >
            <i class="bi bi-share text-coral"></i>
            <strong>{{ a.platform_label }}</strong>
            <span>@{{ a.handle }}</span>
            <span class="badge" :class="a.is_approved ? 'bg-success' : 'bg-warning text-dark'">
              {{ a.is_approved ? 'Approuvé' : 'En attente' }}
            </span>
            <span v-if="!a.is_approved" class="text-muted">
              • Bio : <code>{{ a.verification_code }}</code>
            </span>
          </div>
        </div>
        <div v-else class="small text-muted">
          Aucun compte social enregistré. Cliquez sur <strong>« Ajouter un compte social »</strong> pour commencer.
        </div>
      </div>
    </div>

    <!-- Campagnes sociales prêtes à publier -->
    <div class="row g-4">
      <div v-for="c in availableCampaigns" :key="c.id" class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4 d-flex flex-column">
            <div class="d-flex gap-3 mb-3">
              <img
                v-if="c.media_url"
                :src="c.media_url"
                :alt="c.title"
                class="rounded-3 border object-fit-cover flex-shrink-0"
                style="width: 78px; height: 78px;"
              />
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <h2 class="h6 fw-bold text-slate mb-1">{{ c.title || c.name }}</h2>
                  <span class="badge bg-coral-subtle text-coral flex-shrink-0">
                    {{ c.unit_price_xaf }} FCFA / clic
                  </span>
                </div>
                <p class="small text-muted mb-0">{{ c.body_text }}</p>
              </div>
            </div>

            <!-- Cas 1 : Pas encore rejoint -->
            <div v-if="!c.placement" class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
              <span class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>Inclure <code>#pub</code> • Maintien {{ minPostHours }}h
              </span>
              <button
                type="button"
                class="btn btn-coral btn-sm fw-semibold"
                :disabled="!approvedAccounts.length"
                @click="joinCampaign(c.id)"
              >
                Obtenir mon lien /c/
              </button>
            </div>

            <!-- Cas 2 : Déjà rejoint -> Tout est directement ici ! -->
            <div v-else class="mt-auto pt-3 border-top">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-semibold text-slate">Votre lien de suivi :</span>
                <span class="badge bg-secondary">{{ c.placement.status_label }}</span>
              </div>

              <div class="input-group input-group-sm mb-3">
                <input :value="c.placement.tracking_url" type="text" class="form-control bg-light" readonly />
                <button
                  type="button"
                  class="btn btn-outline-dark"
                  @click="copyText(c.placement.tracking_url, 'link-' + c.id)"
                >
                  {{ copiedKey === 'link-' + c.id ? 'Copié !' : 'Copier lien' }}
                </button>
                <button
                  type="button"
                  class="btn btn-coral"
                  @click="copyText((c.body_text || c.title) + ' ' + c.placement.tracking_url + ' #pub', 'all-' + c.id)"
                >
                  {{ copiedKey === 'all-' + c.id ? 'Copié !' : 'Copier post complet' }}
                </button>
              </div>

              <div v-if="c.placement.status === 'pending_proof'" class="bg-light rounded-3 p-2">
                <div class="row g-2 align-items-center">
                  <div class="col-sm-7">
                    <input
                      v-model="getProofState(c.placement.id).post_url"
                      type="url"
                      class="form-control form-control-sm"
                      placeholder="Collez l'URL de votre post publié..."
                    />
                  </div>
                  <div class="col-sm-3 form-check small mb-0 ps-4">
                    <input
                      :id="'pub-' + c.placement.id"
                      v-model="getProofState(c.placement.id).has_disclosure_tag"
                      type="checkbox"
                      class="form-check-input"
                    />
                    <label :for="'pub-' + c.placement.id" class="form-check-label">#pub ok</label>
                  </div>
                  <div class="col-sm-2">
                    <button type="button" class="btn btn-success btn-sm w-100" @click="submitProof(c.placement.id)">
                      Envoyer
                    </button>
                  </div>
                </div>
              </div>
              <div v-else-if="c.placement.post_url" class="small text-muted text-truncate">
                Preuve soumise : <a :href="c.placement.post_url" target="_blank">{{ c.placement.post_url }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!availableCampaigns.length" class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
          Aucune campagne sociale active pour le moment.
        </div>
      </div>
    </div>

    <!-- Modal d'ajout de compte social -->
    <div v-if="showAddModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(17, 28, 38, 0.55);" @click.self="showAddModal = false">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg rounded-4" @submit.prevent="addAccount">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold text-slate">Ajouter un compte social</h5>
            <button type="button" class="btn-close" @click="showAddModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex gap-2 mb-3">
              <a href="/publisher/social/oauth/facebook/redirect" class="btn btn-outline-primary btn-sm w-50">
                <i class="bi bi-facebook me-1"></i>OAuth Facebook
              </a>
              <a href="/publisher/social/oauth/linkedin/redirect" class="btn btn-outline-info btn-sm w-50">
                <i class="bi bi-linkedin me-1"></i>OAuth LinkedIn
              </a>
            </div>
            <div class="text-center small text-muted mb-3">— ou ajout manuel avec code bio —</div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Réseau social</label>
              <select v-model="accountForm.platform" class="form-select">
                <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Identifiant / Nom de page</label>
              <input v-model="accountForm.handle" type="text" class="form-control" placeholder="ex: moncompte" required />
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">URL du profil ou de la page</label>
              <input v-model="accountForm.profile_url" type="url" class="form-control" required />
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-light border" @click="showAddModal = false">Annuler</button>
            <button type="submit" class="btn btn-coral fw-semibold" :disabled="accountForm.processing">
              Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </PublisherLayout>
</template>

<style scoped>
.text-slate { color: #192733 !important; }
.text-coral { color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover:not(:disabled) { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
