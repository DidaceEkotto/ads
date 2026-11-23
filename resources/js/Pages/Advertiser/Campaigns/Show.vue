<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdvertiserLayout from '../../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  campaign: { type: Object, required: true },
  summary: { type: Object, required: true },
  abComparison: { type: Array, default: () => [] },
  sitesBreakdown: { type: Array, required: true },
  byDevice: { type: Array, required: true },
  byGeo: { type: Array, required: true },
  blockedClicks: { type: Array, required: true },
  dailyComparison: { type: Array, required: true },
});

const hoveredIdx = ref(null);

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

const maxVal = computed(() => {
  const all = props.dailyComparison.flatMap((d) => [d.views, d.unique_visitors, d.clicks]);
  return Math.max(5, ...all);
});

const pointsViews = computed(() => buildPoints('views'));
const pointsClients = computed(() => buildPoints('unique_visitors'));
const pointsClicks = computed(() => buildPoints('clicks'));

function buildPoints(key) {
  const w = 640;
  const h = 180;
  const pad = 20;
  const n = Math.max(1, props.dailyComparison.length - 1);
  return props.dailyComparison.map((d, i) => {
    const x = pad + (i / n) * (w - pad * 2);
    const y = h - pad - ((d[key] || 0) / maxVal.value) * (h - pad * 2);
    return { x: Math.round(x), y: Math.round(y), ...d };
  });
}

const totalGeoViews = computed(() =>
  Math.max(1, props.byGeo.reduce((acc, g) => acc + g.views, 0))
);
const totalDeviceViews = computed(() =>
  Math.max(1, props.byDevice.reduce((acc, d) => acc + d.views, 0))
);
</script>

<template>
  <AdvertiserLayout>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">{{ campaign.name }}</h1>
        <div class="d-flex align-items-center gap-2 small text-muted">
          <span class="badge bg-light text-dark border">{{ campaign.status_label }}</span>
          <span>{{ campaign.billing_model }}</span>
          <span>• Dépensé : {{ formatXaf(campaign.spent_xaf) }} / {{ formatXaf(campaign.total_budget_xaf) }}</span>
        </div>
      </div>
      <Link href="/advertiser/campaigns" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour aux campagnes
      </Link>
    </div>

    <!-- KPIs Preuve d'audience réelle -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
          <div class="text-muted small">Sites présents</div>
          <div class="fs-3 fw-bold text-slate">{{ summary.sites_count }}</div>
        </div></div>
      </div>
      <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
          <div class="text-muted small">Vues totales</div>
          <div class="fs-3 fw-bold text-primary">{{ summary.views_count }}</div>
        </div></div>
      </div>
      <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
          <div class="text-muted small">Clients uniques</div>
          <div class="fs-3 fw-bold text-coral">{{ summary.unique_visitors_count }}</div>
        </div></div>
      </div>
      <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
          <div class="text-muted small">Clics valides ({{ summary.ctr }} %)</div>
          <div class="fs-3 fw-bold text-success">{{ summary.valid_clicks_count }}</div>
        </div></div>
      </div>
      <div class="col-12 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-light"><div class="card-body">
          <div class="text-muted small">Bots bloqués (0 FCFA)</div>
          <div class="fs-3 fw-bold text-danger">{{ summary.blocked_bots_count }}</div>
        </div></div>
      </div>
      <div class="col-12 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body">
          <div class="text-muted small">Conversions (CPA)</div>
          <div class="fs-3 fw-bold text-dark">{{ summary.conversions_count }}</div>
        </div></div>
      </div>
    </div>

    <!-- Test A/B & Pixel de conversion CPA -->
    <div class="row g-4 mb-4">
      <div v-if="abComparison.length" class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-3 px-4 fw-bold text-slate">Comparaison A/B des visuels</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Variante</th><th>Vues</th><th>Clics</th><th>CTR</th></tr></thead>
              <tbody>
                <tr v-for="v in abComparison" :key="v.id">
                  <td class="small fw-semibold">{{ v.title }} <span class="badge bg-light text-muted border">{{ v.media_type }}</span></td>
                  <td>{{ v.views }}</td>
                  <td>{{ v.clicks }}</td>
                  <td class="fw-bold text-coral">{{ v.ctr }} %</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 px-4">
          <div class="fw-bold text-slate small mb-1"><i class="bi bi-code-square text-coral me-1"></i>Pixel de conversion (CPA)</div>
          <p class="small text-muted mb-2">Collez cette balise sur votre page de confirmation de commande pour mesurer vos ventes :</p>
          <code class="small bg-light p-2 rounded border d-block">&lt;img src="{{ campaign.conversion_pixel_url }}?event=purchase&amp;value=5000" width="1" height="1" alt="" /&gt;</code>
        </div>
      </div>
    </div>

    <!-- Courbe de comparaison 14 jours -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h5 fw-bold text-slate mb-0">Courbe de comparaison (Vues vs Clients uniques vs Clics)</h2>
          <div v-if="hoveredIdx !== null" class="small text-coral fw-semibold mt-1">
            {{ dailyComparison[hoveredIdx].date }} : {{ dailyComparison[hoveredIdx].views }} vues •
            {{ dailyComparison[hoveredIdx].unique_visitors }} clients uniques •
            {{ dailyComparison[hoveredIdx].clicks }} clics valides
          </div>
        </div>
        <div class="d-flex gap-3 small">
          <span><span class="dot bg-primary"></span> Vues</span>
          <span><span class="dot bg-coral"></span> Clients uniques</span>
          <span><span class="dot bg-success"></span> Clics</span>
        </div>
      </div>
      <div class="card-body px-4 pb-4">
        <svg viewBox="0 0 640 185" class="w-100" style="max-height: 220px;">
          <line x1="20" y1="40" x2="620" y2="40" stroke="#f1f3f5" />
          <line x1="20" y1="100" x2="620" y2="100" stroke="#f1f3f5" />
          <line x1="20" y1="160" x2="620" y2="160" stroke="#dee2e6" />
          <polyline fill="none" stroke="#0d6efd" stroke-width="2.5" :points="pointsViews.map(p => `${p.x},${p.y}`).join(' ')" />
          <polyline fill="none" stroke="#ff5c35" stroke-width="2.5" :points="pointsClients.map(p => `${p.x},${p.y}`).join(' ')" />
          <polyline fill="none" stroke="#198754" stroke-width="2.5" :points="pointsClicks.map(p => `${p.x},${p.y}`).join(' ')" />
          <circle
            v-for="(p, idx) in pointsClients"
            :key="p.date"
            :cx="p.x"
            :cy="p.y"
            :r="hoveredIdx === idx ? 6 : 3.5"
            fill="#ff5c35"
            style="cursor: pointer;"
            @mouseenter="hoveredIdx = idx"
            @mouseleave="hoveredIdx = null"
          />
        </svg>
        <div class="d-flex justify-content-between small text-muted px-2">
          <span>{{ dailyComparison[0]?.date }}</span>
          <span>{{ dailyComparison[Math.floor(dailyComparison.length / 2)]?.date }}</span>
          <span>{{ dailyComparison[dailyComparison.length - 1]?.date }}</span>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <!-- Géolocalisation Pays & Villes -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h5 fw-bold text-slate mb-0"><i class="bi bi-geo-alt text-coral me-2"></i>Pays &amp; Villes (GeoIP)</h2>
          </div>
          <div class="card-body px-4">
            <div v-for="g in byGeo" :key="g.country + g.city" class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="fw-semibold">{{ g.city }}, {{ g.country }}</span>
                <span class="text-muted">{{ g.views }} vues • {{ g.unique_visitors }} clients</span>
              </div>
              <div class="progress" style="height: 7px;">
                <div class="progress-bar bg-coral" :style="{ width: Math.round((g.views / totalGeoViews) * 100) + '%' }"></div>
              </div>
            </div>
            <div v-if="!byGeo.length" class="text-muted small text-center py-3">Aucune donnée géographique.</div>
          </div>
        </div>
      </div>

      <!-- Appareils & Protection Anti-bot -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h5 fw-bold text-slate mb-0"><i class="bi bi-phone text-coral me-2"></i>Types d'appareils</h2>
          </div>
          <div class="card-body px-4">
            <div v-for="d in byDevice" :key="d.device_type" class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-capitalize fw-semibold">{{ d.device_type }}</span>
                <span class="text-muted">{{ d.views }} vues • {{ d.unique_visitors }} clients</span>
              </div>
              <div class="progress" style="height: 7px;">
                <div class="progress-bar bg-dark" :style="{ width: Math.round((d.views / totalDeviceViews) * 100) + '%' }"></div>
              </div>
            </div>
            <div v-if="!byDevice.length" class="text-muted small text-center py-2">Aucune donnée.</div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h6 fw-bold text-slate mb-0"><i class="bi bi-shield-check text-success me-2"></i>Preuve d'authenticité (Bots &amp; doublons bloqués)</h2>
          </div>
          <div class="card-body px-4">
            <div v-for="b in blockedClicks" :key="b.reason" class="d-flex justify-content-between small py-1 border-bottom">
              <span class="text-muted">Motif : <code>{{ b.reason }}</code></span>
              <span class="fw-bold text-danger">{{ b.total }} bloqué(s)</span>
            </div>
            <div v-if="!blockedClicks.length" class="small text-success">
              <i class="bi bi-check-circle me-1"></i>Aucune tentative de bot détectée sur cette campagne.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sites diffuseurs -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 px-4">
        <h2 class="h5 fw-bold text-slate mb-0">Sites diffuseurs ({{ summary.sites_count }})</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th>Site</th><th>Vues</th><th>Clients uniques</th><th>Clics valides</th><th>CTR</th></tr>
          </thead>
          <tbody>
            <tr v-for="s in sitesBreakdown" :key="s.id">
              <td><div class="fw-bold">{{ s.name }}</div><div class="small text-muted">{{ s.domain }}</div></td>
              <td>{{ s.views }}</td>
              <td class="text-coral fw-semibold">{{ s.unique_visitors }}</td>
              <td>{{ s.clicks }}</td>
              <td>{{ s.ctr }} %</td>
            </tr>
            <tr v-if="!sitesBreakdown.length"><td colspan="5" class="text-center text-muted py-4">Aucune diffusion enregistrée.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdvertiserLayout>
</template>

<style scoped>
.text-slate { color: #192733 !important; }
.text-coral { color: #ff5c35 !important; }
.bg-coral { background-color: #ff5c35 !important; }
.dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
</style>
