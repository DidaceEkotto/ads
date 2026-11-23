<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FileDropzone from '../../../Components/FileDropzone.vue';
import AdvertiserLayout from '../../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  types: { type: Array, required: true },
  billingModels: { type: Array, required: true },
  formats: { type: Array, required: true },
  cameroonCities: { type: Array, required: true },
  categories: { type: Array, required: true },
  walletBalanceXaf: { type: Number, default: 0 },
  pricing: { type: Object, required: true },
});

const form = useForm({
  name: '',
  type: 'web',
  billing_model: 'cpc',
  duration_days: 7,
  starts_at: '',
  title: '',
  body_text: '',
  destination_url: 'https://',
  format: 'square_300x250',
  media: null,
  title_b: '',
  media_b: null,
  cities: ['Douala', 'Yaoundé'],
  languages: ['fr'],
  devices: ['mobile', 'desktop'],
  categories: ['actualites'],
});

const estimatedTotalXaf = computed(() =>
  Math.max(1, Number(form.duration_days || 1)) * props.pricing.daily_campaign_cost_xaf
);

const hasEnoughBalance = computed(() => props.walletBalanceXaf >= estimatedTotalXaf.value);
const missingAmountXaf = computed(() => Math.max(0, estimatedTotalXaf.value - props.walletBalanceXaf));

const appliedUnitPriceXaf = computed(() => {
  if (form.billing_model === 'cpm') return props.pricing.view_unit_price_xaf;
  if (form.billing_model === 'flat_post') return props.pricing.daily_campaign_cost_xaf;
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

const previewDomain = computed(() => {
  try {
    return new URL(form.destination_url).hostname.replace(/^www\./, '');
  } catch {
    return 'votre-site.cm';
  }
});

function toggleAllCities() {
  if (allCitiesSelected.value) {
    form.cities = [];
  } else {
    form.cities = [...props.cameroonCities];
  }
}

function submit() {
  form.post('/advertiser/campaigns', {
    forceFormData: true,
  });
}
</script>

<template>
  <AdvertiserLayout>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">
          <i class="bi bi-megaphone-fill text-coral me-2"></i>Créer une campagne publicitaire
        </h1>
        <p class="text-muted small mb-0">
          Configurez votre budget en FCFA, importez votre visuel optimisé et choisissez vos villes cibles.
        </p>
      </div>
      <Link href="/advertiser/campaigns" class="btn btn-outline-secondary btn-sm fw-medium">
        <i class="bi bi-arrow-left me-1"></i>Retour aux campagnes
      </Link>
    </div>

    <form class="row g-4" @submit.prevent="submit">
      <!-- Colonne gauche : Budget & Visuel -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-coral text-uppercase mb-3">
              <i class="bi bi-sliders me-1"></i>1. Paramètres &amp; Budget (FCFA)
            </h2>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold">Nom de la campagne *</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.name }"
                  placeholder="ex : Promo Rentrée Scolaire Douala 2026"
                  required
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Canal de diffusion</label>
                <select v-model="form.type" class="form-select">
                  <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Modèle de facturation</label>
                <select v-model="form.billing_model" class="form-select">
                  <option v-for="b in billingModels" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
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
                <div class="form-text">Tarif fixe : {{ formatXaf(pricing.daily_campaign_cost_xaf) }} / jour</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold">Récapitulatif tarifaire</label>
                <div
                  class="p-3 rounded-3 border"
                  :class="hasEnoughBalance ? 'bg-coral-subtle border-coral' : 'bg-danger-subtle border-danger'"
                >
                  <div class="small text-muted">Total à payer ({{ form.duration_days }} j × {{ formatXaf(pricing.daily_campaign_cost_xaf) }})</div>
                  <div class="fs-4 fw-bold" :class="hasEnoughBalance ? 'text-coral' : 'text-danger'">
                    {{ formatXaf(estimatedTotalXaf) }}
                  </div>
                  <div class="small text-dark mt-1">
                    Tarif unitaire appliqué : <strong>{{ formatXaf(appliedUnitPriceXaf) }}</strong>
                    <span v-if="form.billing_model === 'cpc'"> / clic</span>
                    <span v-else-if="form.billing_model === 'cpm'"> / vue au scroll</span>
                  </div>
                  <hr class="my-2" />
                  <div class="d-flex justify-content-between align-items-center small">
                    <span>Votre wallet : <strong>{{ formatXaf(walletBalanceXaf) }}</strong></span>
                    <span v-if="hasEnoughBalance" class="badge bg-success">Solde suffisant</span>
                    <span v-else class="badge bg-danger">Solde insuffisant</span>
                  </div>
                  <div v-if="!hasEnoughBalance" class="mt-2">
                    <div class="small text-danger fw-medium mb-2">
                      Il manque <strong>{{ formatXaf(missingAmountXaf) }}</strong> dans votre wallet.
                    </div>
                    <Link href="/advertiser/wallet" class="btn btn-sm btn-danger w-100 fw-semibold">
                      <i class="bi bi-wallet2 me-1"></i>Recharger mon wallet
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
                  placeholder="ex : -30% sur toute la boutique à Douala !"
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
                  placeholder="https://votre-boutique.cm/offre"
                  required
                />
                <div v-if="form.errors.destination_url" class="invalid-feedback">{{ form.errors.destination_url }}</div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Texte d'accompagnement (natif / réseaux sociaux)</label>
                <textarea
                  v-model="form.body_text"
                  rows="3"
                  class="form-control"
                  placeholder="ex : Commandez en ligne et payez à la livraison à Douala et Yaoundé."
                ></textarea>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Visuel publicitaire (Variante A) *</label>
                <FileDropzone
                  v-model="form.media"
                  accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm"
                  label="Glissez-déposez votre image, GIF ou vidéo courte, ou cliquez"
                  hint="Formats : JPG/PNG/WebP (< 150 Ko), GIF (< 500 Ko), MP4/WebM (< 1 Mo)"
                  :error="form.errors.media"
                />
              </div>

              <div class="col-12 border-top pt-3 mt-3">
                <div class="small fw-bold text-slate mb-2">
                  <i class="bi bi-split text-coral me-1"></i>Test A/B (Variante B optionnelle)
                </div>
                <input
                  v-model="form.title_b"
                  type="text"
                  class="form-control form-control-sm mb-2"
                  placeholder="Titre alternatif pour la Variante B (optionnel)"
                />
                <FileDropzone
                  v-model="form.media_b"
                  accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm"
                  label="Glissez-déposez la Variante B (optionnel)"
                  hint="Permet de comparer quel visuel obtient le meilleur CTR"
                  :error="form.errors.media_b"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Colonne droite : Aperçu en direct, Ciblage & Soumission obligatoire -->
      <div class="col-lg-5">
        <!-- Aperçu en direct sur un site web -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h6 fw-bold text-coral text-uppercase mb-0">
                <i class="bi bi-eye me-1"></i>Aperçu sur site web
              </h2>
              <span class="badge bg-light text-muted border small">{{ form.format }}</span>
            </div>

            <div class="border rounded-3 overflow-hidden bg-white">
              <!-- Fausse barre navigateur -->
              <div class="bg-light border-bottom px-3 py-2 d-flex align-items-center gap-2 small text-muted">
                <span class="rounded-circle bg-danger d-inline-block" style="width:8px;height:8px;"></span>
                <span class="rounded-circle bg-warning d-inline-block" style="width:8px;height:8px;"></span>
                <span class="rounded-circle bg-success d-inline-block" style="width:8px;height:8px;"></span>
                <span class="bg-white border rounded px-2 py-0 flex-grow-1 text-truncate" style="font-size: 0.72rem;">
                  actu-cameroun.cm/article-economie
                </span>
              </div>

              <!-- Simulation de page d'article -->
              <div class="p-3">
                <div class="bg-light rounded mb-2" style="height: 10px; width: 70%;"></div>
                <div class="bg-light rounded mb-3" style="height: 8px; width: 95%;"></div>

                <!-- Format Natif ou Social -->
                <div v-if="form.format === 'native' || form.format === 'social'" class="border rounded-3 p-2 bg-light bg-opacity-50 d-flex gap-3 align-items-center">
                  <div class="flex-shrink-0 rounded-2 overflow-hidden bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                    <img v-if="mediaPreviewUrl" :src="mediaPreviewUrl" alt="Aperçu" class="w-100 h-100 object-fit-cover" />
                    <i v-else class="bi bi-image text-muted fs-4"></i>
                  </div>
                  <div class="min-w-0 flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">Sponsorisé • {{ previewDomain }}</span>
                    </div>
                    <div class="fw-bold text-dark small text-truncate">{{ form.title || 'Titre de votre annonce' }}</div>
                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                      {{ form.body_text || 'Votre texte d\'accompagnement apparaîtra ici.' }}
                    </div>
                  </div>
                </div>

                <!-- Format Bannière 728x90 -->
                <div v-else-if="form.format === 'banner_728x90'" class="border rounded-2 overflow-hidden bg-light text-center position-relative" style="height: 76px;">
                  <img v-if="mediaPreviewUrl" :src="mediaPreviewUrl" alt="Aperçu bannière" class="w-100 h-100 object-fit-cover" />
                  <div v-else class="h-100 d-flex flex-column align-items-center justify-content-center text-muted small">
                    <i class="bi bi-aspect-ratio"></i>
                    <span>Bannière 728×90 — {{ form.title || 'Votre titre' }}</span>
                  </div>
                </div>

                <!-- Format Vertical 160x600 -->
                <div v-else-if="form.format === 'vertical_160x600'" class="row g-2 align-items-stretch">
                  <div class="col-8">
                    <div class="bg-light rounded mb-2" style="height: 8px; width: 100%;"></div>
                    <div class="bg-light rounded mb-2" style="height: 8px; width: 88%;"></div>
                    <div class="bg-light rounded mb-2" style="height: 8px; width: 92%;"></div>
                    <div class="bg-light rounded" style="height: 8px; width: 65%;"></div>
                  </div>
                  <div class="col-4">
                    <div class="border rounded-2 overflow-hidden bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                      <img v-if="mediaPreviewUrl" :src="mediaPreviewUrl" alt="Aperçu vertical" class="w-100 h-100 object-fit-cover" />
                      <span v-else class="text-muted text-center px-1" style="font-size: 0.65rem;">160×600</span>
                    </div>
                  </div>
                </div>

                <!-- Format Carré 300x250 (défaut) -->
                <div v-else class="mx-auto border rounded-3 overflow-hidden bg-light text-center" style="max-width: 240px;">
                  <div class="d-flex align-items-center justify-content-center" style="height: 165px;">
                    <img v-if="mediaPreviewUrl" :src="mediaPreviewUrl" alt="Aperçu carré" class="w-100 h-100 object-fit-cover" />
                    <div v-else class="text-muted small p-3">
                      <i class="bi bi-image fs-2 d-block mb-1"></i>
                      300×250<br />{{ form.title || 'Titre de l\'annonce' }}
                    </div>
                  </div>
                  <div class="bg-white border-top px-2 py-1 text-start d-flex justify-content-between align-items-center">
                    <span class="small fw-semibold text-truncate">{{ form.title || 'Titre de votre annonce' }}</span>
                    <span class="badge bg-coral text-white ms-1" style="font-size: 0.65rem;">Voir</span>
                  </div>
                </div>

                <div class="bg-light rounded mt-3" style="height: 8px; width: 82%;"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-coral text-uppercase mb-3">
              <i class="bi bi-geo-alt-fill me-1"></i>3. Ciblage géographique &amp; Audience
            </h2>

            <!-- Villes obligatoires avec bouton Tout sélectionner -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label small fw-semibold mb-0">
                  Villes cibles (Cameroun) *
                </label>
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0 text-coral fw-semibold text-decoration-none"
                  @click="toggleAllCities"
                >
                  {{ allCitiesSelected ? 'Tout désélectionner' : 'Toutes les villes' }}
                </button>
              </div>

              <div class="d-flex flex-wrap gap-2 p-3 rounded-3 bg-light border" :class="{ 'border-danger': form.errors.cities }">
                <label
                  v-for="city in cameroonCities"
                  :key="city"
                  class="city-chip"
                  :class="{ 'is-selected': form.cities.includes(city) }"
                >
                  <input v-model="form.cities" :value="city" type="checkbox" class="d-none" />
                  <i class="bi me-1" :class="form.cities.includes(city) ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                  {{ city }}
                </label>
              </div>
              <div v-if="form.errors.cities" class="text-danger small mt-1">{{ form.errors.cities }}</div>
              <div class="form-text">Sélectionnez une, plusieurs ou toutes les villes (obligatoire).</div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Appareils</label>
              <div class="d-flex flex-wrap gap-3">
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

            <!-- Mention obligatoire non négociable -->
            <div class="alert border-coral bg-coral-subtle small mb-4">
              <i class="bi bi-shield-check text-coral me-1"></i>
              <strong>Validation obligatoire :</strong> toute campagne créée est automatiquement soumise à l'équipe d'administration avant diffusion.
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
              Soumettre la campagne pour validation
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
