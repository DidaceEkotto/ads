<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdvertiserLayout from '../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  kpis: { type: Object, required: true },
  dailyStats: { type: Array, required: true },
  campaignComparison: { type: Array, required: true },
  byDevice: { type: Array, required: true },
  lowBudgetCampaigns: { type: Array, required: true },
});

const hoveredIndex = ref(null);
const chartMetric = ref('clicks'); // 'clicks' | 'impressions'

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

const maxChartVal = computed(() => {
  const vals = props.dailyStats.map((d) => d[chartMetric.value] || 0);
  return Math.max(10, ...vals);
});

const chartPoints = computed(() => {
  const w = 640;
  const h = 180;
  const pad = 20;
  const n = Math.max(1, props.dailyStats.length - 1);
  return props.dailyStats.map((d, i) => {
    const x = pad + (i / n) * (w - pad * 2);
    const y = h - pad - ((d[chartMetric.value] || 0) / maxChartVal.value) * (h - pad * 2);
    return { x: Math.round(x), y: Math.round(y), ...d };
  });
});

const polylinePoints = computed(() => chartPoints.value.map((p) => `${p.x},${p.y}`).join(' '));

const areaPath = computed(() => {
  if (!chartPoints.value.length) return '';
  const first = chartPoints.value[0];
  const last = chartPoints.value[chartPoints.value.length - 1];
  return `M ${first.x},160 L ${polylinePoints.value} L ${last.x},160 Z`;
});

const totalDeviceImpressions = computed(() =>
  Math.max(1, props.byDevice.reduce((acc, d) => acc + Number(d.impressions || 0), 0))
);
</script>

<template>
  <AdvertiserLayout>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">Vue d'ensemble des performances</h1>
        <p class="text-muted small mb-0">Comparaison des 7 derniers jours par rapport à la période précédente</p>
      </div>
      <Link href="/advertiser/campaigns/create" class="btn btn-coral fw-semibold">
        <i class="bi bi-plus-lg me-1"></i>Nouvelle campagne
      </Link>
    </div>

    <div v-if="lowBudgetCampaigns.length" class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <strong>Budget bas (&lt; 20 %) :</strong>
      <span v-for="(c, idx) in lowBudgetCampaigns" :key="c.id">
        {{ c.name }} ({{ formatXaf(c.remaining_xaf) }} restant){{ idx < lowBudgetCampaigns.length - 1 ? ', ' : '' }}
      </span>
    </div>

    <!-- KPIs avec indicateurs de hausse / baisse -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small fw-medium">Impressions</span>
              <span class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-eye"></i></span>
            </div>
            <div class="fs-3 fw-bold text-slate mb-1">{{ new Intl.NumberFormat('fr-FR').format(kpis.impressions) }}</div>
            <span class="badge" :class="kpis.trends.impressions >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              <i class="bi" :class="kpis.trends.impressions >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
              {{ kpis.trends.impressions >= 0 ? '+' : '' }}{{ kpis.trends.impressions }} % (7j)
            </span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small fw-medium">Clics valides</span>
              <span class="kpi-icon bg-coral-subtle text-coral"><i class="bi bi-cursor"></i></span>
            </div>
            <div class="fs-3 fw-bold text-slate mb-1">{{ new Intl.NumberFormat('fr-FR').format(kpis.clicks) }}</div>
            <span class="badge" :class="kpis.trends.clicks >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              <i class="bi" :class="kpis.trends.clicks >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
              {{ kpis.trends.clicks >= 0 ? '+' : '' }}{{ kpis.trends.clicks }} % (7j)
            </span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small fw-medium">CTR moyen</span>
              <span class="kpi-icon bg-success-subtle text-success"><i class="bi bi-percent"></i></span>
            </div>
            <div class="fs-3 fw-bold text-slate mb-1">{{ kpis.ctr }} %</div>
            <span class="badge" :class="kpis.trends.ctr >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              <i class="bi" :class="kpis.trends.ctr >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
              {{ kpis.trends.ctr >= 0 ? '+' : '' }}{{ kpis.trends.ctr }} % (7j)
            </span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small fw-medium">Dépensé (CPC moy. {{ formatXaf(kpis.avg_cpc) }})</span>
              <span class="kpi-icon bg-dark-subtle text-dark"><i class="bi bi-cash-coin"></i></span>
            </div>
            <div class="fs-3 fw-bold text-slate mb-1">{{ formatXaf(kpis.spend_xaf) }}</div>
            <span class="badge bg-light text-muted border">
              {{ kpis.trends.spend >= 0 ? '+' : '' }}{{ kpis.trends.spend }} % vs 7j préc.
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Courbe d'évolution 14 jours + Répartition appareils -->
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h2 class="h5 fw-bold text-slate mb-0">Évolution sur 14 jours</h2>
              <div v-if="hoveredIndex !== null && chartPoints[hoveredIndex]" class="small text-coral fw-medium mt-1">
                {{ chartPoints[hoveredIndex].date }} : {{ chartPoints[hoveredIndex].clicks }} clics • {{ chartPoints[hoveredIndex].impressions }} imp. • {{ formatXaf(chartPoints[hoveredIndex].spend) }}
              </div>
            </div>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn" :class="chartMetric === 'clicks' ? 'btn-coral' : 'btn-outline-secondary'" @click="chartMetric = 'clicks'">Clics</button>
              <button type="button" class="btn" :class="chartMetric === 'impressions' ? 'btn-coral' : 'btn-outline-secondary'" @click="chartMetric = 'impressions'">Impressions</button>
            </div>
          </div>
          <div class="card-body px-4 pb-4">
            <svg viewBox="0 0 640 185" class="w-100" style="max-height: 220px;">
              <defs>
                <linearGradient id="advChartGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#ff5c35" stop-opacity="0.32" />
                  <stop offset="100%" stop-color="#ff5c35" stop-opacity="0.0" />
                </linearGradient>
              </defs>
              <line x1="20" y1="40" x2="620" y2="40" stroke="#f1f3f5" />
              <line x1="20" y1="100" x2="620" y2="100" stroke="#f1f3f5" />
              <line x1="20" y1="160" x2="620" y2="160" stroke="#dee2e6" />
              <path v-if="areaPath" :d="areaPath" fill="url(#advChartGrad)" />
              <polyline v-if="polylinePoints" fill="none" stroke="#ff5c35" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" :points="polylinePoints" />
              <g v-for="(pt, idx) in chartPoints" :key="pt.date">
                <circle
                  :cx="pt.x"
                  :cy="pt.y"
                  :r="hoveredIndex === idx ? 6 : 4"
                  fill="#ff5c35"
                  stroke="#fff"
                  stroke-width="2"
                  style="cursor: pointer;"
                  @mouseenter="hoveredIndex = idx"
                  @mouseleave="hoveredIndex = null"
                />
              </g>
            </svg>
            <div class="d-flex justify-content-between small text-muted px-2">
              <span>{{ dailyStats[0]?.date }}</span>
              <span>{{ dailyStats[Math.floor(dailyStats.length / 2)]?.date }}</span>
              <span>{{ dailyStats[dailyStats.length - 1]?.date }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h5 fw-bold text-slate mb-0">Appareils</h2>
          </div>
          <div class="card-body px-4">
            <div v-for="dev in byDevice" :key="dev.device_type" class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-capitalize fw-semibold">{{ dev.device_type }}</span>
                <span class="text-muted">{{ dev.impressions }} imp. / {{ dev.clicks }} clics</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div
                  class="progress-bar bg-coral"
                  :style="{ width: Math.round((dev.impressions / totalDeviceImpressions) * 100) + '%' }"
                ></div>
              </div>
            </div>
            <div v-if="!byDevice.length" class="text-muted small text-center py-4">
              Aucune donnée d'appareil enregistrée.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Comparaison de chaque campagne -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 px-4">
        <h2 class="h5 fw-bold text-slate mb-0">Comparaison des campagnes</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Campagne</th>
              <th>Statut</th>
              <th>Impressions</th>
              <th>Clics</th>
              <th>CTR</th>
              <th>Tendance (7j)</th>
              <th>Consommation budget</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in campaignComparison" :key="c.id">
              <td>
                <div class="fw-bold text-slate">{{ c.name }}</div>
                <div class="small text-muted">{{ c.billing_model }} • {{ formatXaf(c.cpc) }}/clic</div>
              </td>
              <td><span class="badge bg-light text-dark border">{{ c.status_label }}</span></td>
              <td>{{ new Intl.NumberFormat('fr-FR').format(c.impressions) }}</td>
              <td class="fw-semibold">{{ new Intl.NumberFormat('fr-FR').format(c.clicks) }}</td>
              <td>{{ c.ctr }} %</td>
              <td>
                <span class="badge" :class="c.trend_percent >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                  <i class="bi" :class="c.trend_percent >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
                  {{ c.trend_percent >= 0 ? '+' : '' }}{{ c.trend_percent }} %
                </span>
              </td>
              <td style="min-width: 180px;">
                <div class="small fw-semibold mb-1">{{ formatXaf(c.spend_xaf) }} / {{ formatXaf(c.total_budget_xaf) }}</div>
                <div class="progress" style="height: 6px;">
                  <div
                    class="progress-bar bg-coral"
                    :style="{ width: Math.min(100, Math.round((c.spend_xaf / Math.max(1, c.total_budget_xaf)) * 100)) + '%' }"
                  ></div>
                </div>
              </td>
            </tr>
            <tr v-if="!campaignComparison.length">
              <td colspan="7" class="text-center text-muted py-4">Aucune campagne pour le moment.</td>
            </tr>
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
.bg-coral-subtle { background-color: #fff1ed !important; }

.btn-coral {
  background-color: #ff5c35;
  border-color: #ff5c35;
  color: #fff;
}
.btn-coral:hover {
  background-color: #e64922;
  border-color: #e64922;
  color: #fff;
}

.kpi-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
}
</style>
