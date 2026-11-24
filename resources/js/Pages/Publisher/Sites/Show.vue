<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

const props = defineProps({
  site: { type: Object, required: true },
  formats: { type: Array, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const copiedId = ref(null);

const autoAdsForm = useForm({
  auto_ads_enabled: props.site.auto_ads_enabled,
  allowed_formats: [...(props.site.allowed_formats || ['native'])],
  custom_cdn_host: props.site.custom_cdn_host || '',
});

function saveAutoAds() {
  autoAdsForm.patch(`/publisher/sites/${props.site.id}/auto-ads`, {
    preserveScroll: true,
  });
}

async function copyCode(text, key) {
  await navigator.clipboard.writeText(text);
  copiedId.value = key;
  setTimeout(() => { copiedId.value = null; }, 2000);
}

const formatMeta = {
  banner_728x90: { title: 'Bannière 728×90', placement: 'Haut de page' },
  square_300x250: { title: 'Carré 300×250', placement: 'Dans l\'article / sidebar' },
  vertical_160x600: { title: 'Vertical 160×600', placement: 'Colonne latérale' },
  native: { title: 'Format Natif', placement: 'Entre paragraphes' },
  modal: { title: 'Popup / Modal', placement: 'Fenêtre modale (1h)' },
};
</script>

<template>
  <PublisherLayout>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h4 fw-bold text-slate mb-1">{{ site.name }} ({{ site.domain }})</h1>
        <span class="badge" :class="site.is_approved ? 'bg-success' : 'bg-warning text-dark'">
          {{ site.status_label }}
        </span>
      </div>
      <Link href="/publisher/sites" class="btn btn-outline-secondary btn-sm">Retour aux sites</Link>
    </div>

    <div v-if="flashSuccess" class="alert alert-success">{{ flashSuccess }}</div>
    <div v-if="flashError" class="alert alert-danger">{{ flashError }}</div>

    <div v-if="!site.is_approved" class="alert alert-warning">
      <i class="bi bi-hourglass-split me-2"></i>
      Ce site est en attente de validation par un administrateur. Votre script unique <code>&lt;head&gt;</code> s'activera dès son approbation.
    </div>

    <!-- Mode Auto-Ads (Script unique dans <head>) -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <span class="badge bg-coral-subtle text-coral mb-1">Script unique • Placement automatique</span>
            <h2 class="h5 fw-bold text-slate mb-0">
              <i class="bi bi-magic text-coral me-2"></i>Publicités Automatiques (&lt;head&gt;)
            </h2>
          </div>
          <div class="form-check form-switch">
            <input id="autoAdsSwitch" v-model="autoAdsForm.auto_ads_enabled" class="form-check-input" type="checkbox" />
            <label for="autoAdsSwitch" class="form-check-label small fw-semibold">
              {{ autoAdsForm.auto_ads_enabled ? 'Activé' : 'Désactivé' }}
            </label>
          </div>
        </div>

        <p class="small text-muted mb-3">
          Copiez cette balise une seule fois dans le <code>&lt;head&gt;</code> de votre site web. Les publicités sélectionnées apparaîtront automatiquement aux meilleurs emplacements.
        </p>

        <div class="bg-dark text-white rounded-3 p-3 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small text-white-50">Code à coller dans &lt;head&gt; ... &lt;/head&gt;</span>
            <button type="button" class="btn btn-coral btn-sm" @click="copyCode(site.auto_ads_snippet, 'head')">
              <i class="bi bi-clipboard me-1"></i>{{ copiedId === 'head' ? 'Copié !' : 'Copier le script' }}
            </button>
          </div>
          <code class="text-warning small">{{ site.auto_ads_snippet }}</code>
        </div>

        <form @submit.prevent="saveAutoAds">
          <div class="mb-3">
            <label class="form-label small fw-semibold mb-2">Types de publicités autorisées sur votre site *</label>
            <div class="row g-2">
              <div v-for="f in formats" :key="f.value" class="col-6 col-md-3">
                <label
                  class="format-visual-card border rounded-3 p-2 h-100 d-flex flex-column cursor-pointer"
                  :class="autoAdsForm.allowed_formats.includes(f.value) ? 'border-coral bg-coral-subtle' : 'bg-white'"
                >
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-slate" style="font-size: 0.75rem;">{{ formatMeta[f.value]?.title || f.label }}</span>
                    <input
                      v-model="autoAdsForm.allowed_formats"
                      :value="f.value"
                      type="checkbox"
                      class="form-check-input m-0 coral-check"
                    />
                  </div>

                  <!-- Miniature compacte -->
                  <div class="border rounded-2 bg-white p-1 mb-1 d-flex flex-column justify-content-center" style="height: 48px;">
                    <template v-if="f.value === 'banner_728x90'">
                      <div class="rounded-1 bg-coral text-white text-center fw-bold mb-1" style="font-size: 0.5rem; padding: 2px 0;">728×90</div>
                      <div class="bg-light rounded mb-1" style="height: 4px; width: 85%;"></div>
                      <div class="bg-light rounded" style="height: 4px; width: 65%;"></div>
                    </template>

                    <template v-else-if="f.value === 'square_300x250'">
                      <div class="row g-1 align-items-center">
                        <div class="col-6">
                          <div class="bg-light rounded mb-1" style="height: 4px; width: 100%;"></div>
                          <div class="bg-light rounded mb-1" style="height: 4px; width: 80%;"></div>
                          <div class="bg-light rounded" style="height: 4px; width: 90%;"></div>
                        </div>
                        <div class="col-6">
                          <div class="rounded-1 bg-coral text-white d-flex align-items-center justify-content-center fw-bold" style="height: 28px; font-size: 0.5rem;">300×250</div>
                        </div>
                      </div>
                    </template>

                    <template v-else-if="f.value === 'vertical_160x600'">
                      <div class="row g-1 align-items-stretch" style="height: 34px;">
                        <div class="col-8 d-flex flex-column justify-content-center gap-1">
                          <div class="bg-light rounded" style="height: 4px; width: 95%;"></div>
                          <div class="bg-light rounded" style="height: 4px; width: 80%;"></div>
                          <div class="bg-light rounded" style="height: 4px; width: 60%;"></div>
                        </div>
                        <div class="col-4">
                          <div class="rounded-1 bg-coral text-white d-flex align-items-center justify-content-center fw-bold h-100" style="font-size: 0.45rem;">160×600</div>
                        </div>
                      </div>
                    </template>

                    <template v-else-if="f.value === 'modal'">
                      <div class="w-100 h-100 rounded-1 bg-dark bg-opacity-25 d-flex align-items-center justify-content-center">
                        <div class="bg-coral text-white rounded-1 px-2 py-1 fw-bold" style="font-size: 0.5rem;">POPUP 1h</div>
                      </div>
                    </template>

                    <template v-else>
                      <div class="border rounded-1 p-1 d-flex align-items-center gap-1 bg-light bg-opacity-50">
                        <div class="rounded-1 bg-coral flex-shrink-0" style="width: 16px; height: 16px;"></div>
                        <div class="flex-grow-1 min-w-0">
                          <div class="bg-secondary bg-opacity-25 rounded mb-1" style="height: 4px; width: 80%;"></div>
                          <div class="bg-secondary bg-opacity-10 rounded" style="height: 3px; width: 55%;"></div>
                        </div>
                      </div>
                    </template>
                  </div>

                  <div class="text-muted" style="font-size: 0.68rem;">
                    {{ formatMeta[f.value]?.placement }}
                  </div>
                </label>
              </div>
            </div>
            <div v-if="autoAdsForm.errors.allowed_formats" class="text-danger small mt-1">{{ autoAdsForm.errors.allowed_formats }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Sous-domaine First-Party / Proxy CNAME (anti-bloqueur avancé, optionnel)</label>
            <input v-model="autoAdsForm.custom_cdn_host" type="url" class="form-control form-control-sm" placeholder="https://media.votre-site.cm" />
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-coral fw-semibold px-4" :disabled="autoAdsForm.processing || !autoAdsForm.allowed_formats.length">
              Enregistrer les formats
            </button>
          </div>
        </form>
      </div>
    </div>
  </PublisherLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.text-coral { color: #ff5c35 !important; }
.bg-coral { background-color: #ff5c35 !important; }
.border-coral { border-color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }
.cursor-pointer { cursor: pointer; }
.coral-check:checked { background-color: #ff5c35; border-color: #ff5c35; }
.format-visual-card { transition: border-color 0.2s ease, background-color 0.2s ease; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
