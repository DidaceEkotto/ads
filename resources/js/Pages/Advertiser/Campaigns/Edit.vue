<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FileDropzone from '../../../Components/FileDropzone.vue';
import AdvertiserLayout from '../../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  campaign: { type: Object, required: true },
  formats: { type: Array, required: true },
  cameroonCities: { type: Array, required: true },
  categories: { type: Array, required: true },
  walletBalanceXaf: { type: Number, default: 0 },
  pricing: { type: Object, required: true },
});

const form = useForm({
  _method: 'put',
  name: props.campaign.name,
  duration_days: props.campaign.duration_days || 7,
  title: props.campaign.title,
  body_text: props.campaign.body_text || '',
  destination_url: props.campaign.destination_url,
  format: props.campaign.format || 'square_300x250',
  media: null,
  cities: [...(props.campaign.cities || ['Douala'])],
  languages: [...(props.campaign.languages || ['fr'])],
  devices: [...(props.campaign.devices || ['mobile', 'desktop'])],
  categories: [...(props.campaign.categories || ['actualites'])],
});

const estimatedTotalXaf = computed(() =>
  Math.max(1, Number(form.duration_days || 1)) * props.pricing.daily_campaign_cost_xaf
);

const hasEnoughBalance = computed(() => props.walletBalanceXaf >= estimatedTotalXaf.value);
const missingAmountXaf = computed(() => Math.max(0, estimatedTotalXaf.value - props.walletBalanceXaf));

const appliedUnitPriceXaf = computed(() => {
  if (props.campaign.billing_model === 'cpm') return props.pricing.view_unit_price_xaf;
  if (props.campaign.billing_model === 'flat_post') return props.pricing.daily_campaign_cost_xaf;
  return props.pricing.cpc_unit_price_xaf;
});

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

const allCitiesSelected = computed(() =>
  props.cameroonCities.length > 0 && form.cities.length === props.cameroonCities.length
);

const mediaPreviewUrl = ref(null);
watch(
  () => form.media,
  (file) => {
    if (mediaPreviewUrl.value) {
      URL.revokeObjectURL(mediaPreviewUrl.value);
      mediaPreviewUrl.value = null;
    }
    if (file) {
      mediaPreviewUrl.value = URL.createObjectURL(file);
    }
  }
);

function toggleAllCities() {
  if (allCitiesSelected.value) {
    form.cities = [];
  } else {
    form.cities = [...props.cameroonCities];
  }
}

function submit() {
  form.post(`/advertiser/campaigns/${props.campaign.id}`, {
    forceFormData: true,
  });
}
</script>

<template>
  <AdvertiserLayout>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">
          <i class="bi bi-pencil-square text-coral me-2"></i>Modifier la campagne
        </h1>
        <p class="text-muted small mb-0">
          Canal : <strong>{{ campaign.type_label }}</strong> · Modèle : <strong>{{ campaign.billing_label }}</strong> · Arrêt prévu : <strong>{{ campaign.ends_at || '—' }}</strong>
        </p>
      </div>
      <Link href="/advertiser/campaigns" class="btn btn-outline-secondary btn-sm fw-medium">
        <i class="bi bi-arrow-left me-1"></i>Retour aux campagnes
      </Link>
    </div>

    <form class="row g-4" @submit.prevent="submit">
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-coral text-uppercase mb-3">
              <i class="bi bi-sliders me-1"></i>1. Paramètres &amp; Durée
            </h2>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold">Nom de la campagne *</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.name }"
                  required
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Durée de la campagne (jours) *</label>
                <div class="d-flex gap-2 mb-2">
                  <button
                    v-for="d in [3, 7, 14, 30]"
                    :key="d"
                    type="button"
                    class="btn btn-sm"
                    :class="form.duration_days === d ? 'btn-coral' : 'btn-outline-secondary'"
                    @click="form.duration_days = d"
                  >
                    {{ d }} j
                  </button>
                </div>
                <input
                  v-model.number="form.duration_days"
                  type="number"
                  min="1"
                  max="365"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.duration_days }"
                  required
                />
                <div v-if="form.errors.duration_days" class="invalid-feedback">{{ form.errors.duration_days }}</div>
                <div class="form-text">Début : {{ campaign.starts_at || 'Maintenant' }} · Tarif : {{ formatXaf(pricing.daily_campaign_cost_xaf) }} / jour</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Récapitulatif tarifaire</label>
                <div
                  class="p-3 rounded-3 border"
                  :class="hasEnoughBalance ? 'bg-coral-subtle border-coral' : 'bg-danger-subtle border-danger'"
                >
                  <div class="small text-muted">Total ({{ form.duration_days }} j × {{ formatXaf(pricing.daily_campaign_cost_xaf) }})</div>
                  <div class="fs-4 fw-bold" :class="hasEnoughBalance ? 'text-coral' : 'text-danger'">
                    {{ formatXaf(estimatedTotalXaf) }}
                  </div>
                  <div class="small text-dark mt-1">
                    Tarif unitaire : <strong>{{ formatXaf(appliedUnitPriceXaf) }}</strong>
                  </div>
                  <hr class="my-2" />
                  <div class="d-flex justify-content-between align-items-center small">
                    <span>Votre wallet : <strong>{{ formatXaf(walletBalanceXaf) }}</strong></span>
                    <span v-if="hasEnoughBalance" class="badge bg-success">Solde suffisant</span>
                    <span v-else class="badge bg-danger">Solde insuffisant</span>
                  </div>
                  <div v-if="!hasEnoughBalance" class="mt-2">
                    <Link href="/advertiser/wallet" class="btn btn-sm btn-danger w-100 fw-semibold">
                      <i class="bi bi-wallet2 me-1"></i>Recharger ({{ formatXaf(missingAmountXaf) }} manquants)
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-coral text-uppercase mb-3">
              <i class="bi bi-image me-1"></i>2. Visuel &amp; Message
            </h2>
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label small fw-semibold">Titre de l'annonce *</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.title }"
                  required
                />
                <div v-if="form.errors.title" class="invalid-feedback">{{ form.errors.title }}</div>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold">Format</label>
                <select v-model="form.format" class="form-select">
                  <option v-for="f in formats" :key="f.value" :value="f.value">{{ f.label }}</option>
                  <option value="social">Post Réseau Social</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Lien de destination *</label>
                <input
                  v-model="form.destination_url"
                  type="url"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.destination_url }"
                  required
                />
                <div v-if="form.errors.destination_url" class="invalid-feedback">{{ form.errors.destination_url }}</div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Texte d'accompagnement</label>
                <textarea v-model="form.body_text" rows="3" class="form-control"></textarea>
              </div>

              <div v-if="campaign.media_url || mediaPreviewUrl" class="col-12">
                <label class="form-label small fw-semibold">Aperçu du visuel</label>
                <div class="border rounded-3 p-2 bg-light text-center">
                  <video
                    v-if="!mediaPreviewUrl && campaign.media_type === 'video'"
                    :src="campaign.media_url"
                    controls
                    muted
                    class="rounded"
                    style="max-height: 180px; max-width: 100%;"
                  ></video>
                  <img
                    v-else
                    :src="mediaPreviewUrl || campaign.media_url"
                    alt="Aperçu"
                    class="img-fluid rounded"
                    style="max-height: 180px;"
                  />
                </div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Remplacer le visuel (optionnel)</label>
                <FileDropzone
                  v-model="form.media"
                  accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm"
                  label="Glissez-déposez un nouveau visuel pour remplacer l'actuel"
                  hint="Formats : JPG/PNG/WebP (< 150 Ko), GIF (< 500 Ko), MP4/WebM (< 1 Mo)"
                  :error="form.errors.media"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-coral text-uppercase mb-3">
              <i class="bi bi-geo-alt me-1"></i>3. Ciblage
            </h2>

            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label small fw-semibold mb-0">Villes cibles *</label>
                <button type="button" class="btn btn-link btn-sm p-0 text-coral text-decoration-none" @click="toggleAllCities">
                  {{ allCitiesSelected ? 'Tout décocher' : 'Tout le Cameroun' }}
                </button>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <label
                  v-for="city in cameroonCities"
                  :key="city"
                  class="city-chip"
                  :class="{ 'is-selected': form.cities.includes(city) }"
                >
                  <input v-model="form.cities" :value="city" type="checkbox" class="d-none" />
                  {{ city }}
                </label>
              </div>
              <div v-if="form.errors.cities" class="text-danger small mt-1">{{ form.errors.cities }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Langues</label>
              <div class="d-flex gap-3">
                <label class="form-check small">
                  <input v-model="form.languages" value="fr" type="checkbox" class="form-check-input coral-check" /> Français
                </label>
                <label class="form-check small">
                  <input v-model="form.languages" value="en" type="checkbox" class="form-check-input coral-check" /> Anglais
                </label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Appareils</label>
              <div class="d-flex gap-3">
                <label class="form-check small">
                  <input v-model="form.devices" value="mobile" type="checkbox" class="form-check-input coral-check" /> Mobile
                </label>
                <label class="form-check small">
                  <input v-model="form.devices" value="desktop" type="checkbox" class="form-check-input coral-check" /> Desktop
                </label>
                <label class="form-check small">
                  <input v-model="form.devices" value="tablet" type="checkbox" class="form-check-input coral-check" /> Tablette
                </label>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-semibold">Catégories de sites</label>
              <div class="d-flex flex-wrap gap-2">
                <label v-for="cat in categories" :key="cat" class="form-check form-check-inline small">
                  <input v-model="form.categories" :value="cat" type="checkbox" class="form-check-input coral-check" />
                  <span class="form-check-label text-capitalize">{{ cat }}</span>
                </label>
              </div>
            </div>

            <div v-if="!hasEnoughBalance || form.errors.wallet" class="alert alert-danger small mb-3">
              <div class="mb-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <span v-if="form.errors.wallet">{{ form.errors.wallet }}</span>
                <span v-else>
                  <strong>Solde wallet insuffisant :</strong> votre wallet contient <strong>{{ formatXaf(walletBalanceXaf) }}</strong> alors que cette campagne coûte <strong>{{ formatXaf(estimatedTotalXaf) }}</strong>.
                </span>
              </div>
              <Link href="/advertiser/wallet" class="btn btn-sm btn-danger fw-semibold">
                <i class="bi bi-plus-circle me-1"></i>Remplir mon wallet ({{ formatXaf(missingAmountXaf) }} manquants)
              </Link>
            </div>

            <button
              type="submit"
              class="btn btn-coral btn-lg w-100 fw-semibold"
              :disabled="form.processing || !form.cities.length || !hasEnoughBalance"
            >
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
              Enregistrer les modifications
            </button>
          </div>
        </div>
      </div>
    </form>
  </AdvertiserLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.text-coral { color: #ff5c35 !important; }
.border-coral { border-color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }

.btn-coral {
  background-color: #ff5c35;
  border-color: #ff5c35;
  color: #fff;
}
.btn-coral:hover:not(:disabled) {
  background-color: #e64922;
  border-color: #e64922;
  color: #fff;
}

.coral-check:checked {
  background-color: #ff5c35;
  border-color: #ff5c35;
}

.city-chip {
  cursor: pointer;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 500;
  background: #fff;
  border: 1px solid #dee2e6;
  color: #495057;
  transition: all 0.15s ease;
}
.city-chip.is-selected {
  background-color: #ff5c35;
  border-color: #ff5c35;
  color: #fff;
}
</style>
