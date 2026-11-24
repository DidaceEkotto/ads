<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

defineProps({
  summary: { type: Object, required: true },
  byCampaign: { type: Array, required: true },
  bySite: { type: Array, required: true },
  bySocial: { type: Array, required: true },
  byDevice: { type: Array, required: true },
  byGeo: { type: Array, required: true },
  dailyStats: { type: Array, required: true },
  recentClicks: { type: Array, required: true },
});

const refreshing = ref(false);

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function formatNumber(n) {
  return new Intl.NumberFormat('fr-FR').format(n ?? 0);
}

function refreshStats() {
  refreshing.value = true;
  router.reload({
    preserveScroll: true,
    onFinish: () => {
      refreshing.value = false;
    },
  });
}
</script>

<template>
  <PublisherLayout>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h1 class="h3 fw-bold text-slate mb-0">
            <i class="bi bi-graph-up-arrow text-coral me-2"></i>Revenus &amp; Statistiques en temps réel
          </h1>
          <span class="badge bg-success-subtle text-success border border-success-subtle">
            <i class="bi bi-broadcast me-1"></i>En direct
          </span>
        </div>
        <p class="text-muted small mb-0">
          Données calculées instantanément sur chaque vue et chaque clic, sans attente de tâche planifiée.
        </p>
      </div>
      <button type="button" class="btn btn-coral btn-sm fw-semibold" :disabled="refreshing" @click="refreshStats">
        <span v-if="refreshing" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="bi bi-arrow-clockwise me-1"></i>Actualiser
      </button>
    </div>

    <!-- KPIs en direct -->
    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="small text-muted mb-1">Revenus totaux générés</div>
            <div class="fs-3 fw-bold text-coral">{{ formatXaf(summary.total_earnings_xaf) }}</div>
            <div class="small text-muted mt-2">
              Aujourd'hui : <strong class="text-success">+{{ formatXaf(summary.today_earnings_xaf) }}</strong>
            </div>
            <div class="small text-muted">
              En attente : <strong>{{ formatXaf(summary.pending_earnings_xaf) }}</strong> · Dispo : <strong>{{ formatXaf(summary.available_balance_xaf) }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="small text-muted mb-1">Clics valides</div>
            <div class="fs-3 fw-bold text-slate">{{ formatNumber(summary.valid_clicks) }}</div>
            <div class="small text-muted mt-2">
              Aujourd'hui : <strong class="text-dark">{{ formatNumber(summary.today_clicks) }}</strong>
            </div>
            <div class="small text-danger">
              Bloqués (anti-fraude) : <strong>{{ formatNumber(summary.blocked_clicks) }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="small text-muted mb-1">Impressions &amp; Visiteurs</div>
            <div class="fs-3 fw-bold text-slate">{{ formatNumber(summary.total_impressions) }}</div>
            <div class="small text-muted mt-2">
              Aujourd'hui : <strong class="text-dark">{{ formatNumber(summary.today_impressions) }}</strong>
            </div>
            <div class="small text-muted">
              Visiteurs uniques : <strong>{{ formatNumber(summary.unique_visitors) }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body">
            <div class="small text-muted mb-1">Performance &amp; Rentabilité</div>
            <div class="fs-3 fw-bold text-success">{{ summary.ctr }} % <span class="fs-6 fw-normal text-muted">CTR</span></div>
            <div class="small text-muted mt-2">
              RPM (1 000 vues) : <strong class="text-dark">{{ formatXaf(summary.rpm_xaf) }}</strong>
            </div>
            <div class="small text-muted">
              Gain moyen / clic : <strong class="text-dark">{{ formatXaf(summary.avg_cpc_xaf) }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gains par publicité diffusée -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white py-3 border-bottom">
        <h2 class="h6 fw-bold text-slate mb-0">
          <i class="bi bi-megaphone text-coral me-2"></i>Revenus générés par publicité diffusée
        </h2>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Publicité / Campagne</th>
              <th>Modèle</th>
              <th class="text-end">Vues</th>
              <th class="text-end">Visiteurs uniques</th>
              <th class="text-end">Clics valides</th>
              <th class="text-end">Bloqués</th>
              <th class="text-end">CTR</th>
              <th class="text-end">En attente</th>
              <th class="text-end">Total généré</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in byCampaign" :key="c.id">
              <td class="fw-semibold text-slate">{{ c.name }}</td>
              <td><span class="badge bg-light text-dark border">{{ c.billing_model }}</span></td>
              <td class="text-end">{{ formatNumber(c.impressions) }}</td>
              <td class="text-end">{{ formatNumber(c.unique_visitors) }}</td>
              <td class="text-end fw-semibold text-success">{{ formatNumber(c.valid_clicks) }}</td>
              <td class="text-end text-danger">{{ formatNumber(c.blocked_clicks) }}</td>
              <td class="text-end">{{ c.ctr }} %</td>
              <td class="text-end text-muted">{{ formatXaf(c.pending_xaf) }}</td>
              <td class="text-end fw-bold text-coral">{{ formatXaf(c.earnings_xaf) }}</td>
            </tr>
            <tr v-if="!byCampaign.length">
              <td colspan="9" class="text-center text-muted py-4">
                Aucune publicité diffusée ou cliquée pour le moment.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Par Site web & Par Réseau Social -->
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold text-slate mb-0">
              <i class="bi bi-globe2 text-coral me-2"></i>Performance par Site web
            </h2>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Site</th>
                  <th class="text-end">Vues</th>
                  <th class="text-end">Clics</th>
                  <th class="text-end">CTR</th>
                  <th class="text-end">Gains</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in bySite" :key="s.id">
                  <td>
                    <div class="fw-semibold">{{ s.name }}</div>
                    <div class="small text-muted">{{ s.domain }}</div>
                  </td>
                  <td class="text-end">{{ formatNumber(s.impressions) }}</td>
                  <td class="text-end text-success fw-semibold">{{ formatNumber(s.valid_clicks) }}</td>
                  <td class="text-end">{{ s.ctr }} %</td>
                  <td class="text-end fw-bold text-coral">{{ formatXaf(s.earnings_xaf) }}</td>
                </tr>
                <tr v-if="!bySite.length">
                  <td colspan="5" class="text-center text-muted py-3">Aucun site web enregistré.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold text-slate mb-0">
              <i class="bi bi-share text-coral me-2"></i>Performance Réseaux Sociaux
            </h2>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Campagne / Compte</th>
                  <th class="text-end">Clics valides</th>
                  <th class="text-end">Bloqués</th>
                  <th class="text-end">Gains</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="soc in bySocial" :key="soc.id">
                  <td>
                    <div class="fw-semibold">{{ soc.campaign_name }}</div>
                    <div class="small text-muted">{{ soc.platform }} · {{ soc.account_name }}</div>
                  </td>
                  <td class="text-end text-success fw-semibold">{{ formatNumber(soc.valid_clicks) }}</td>
                  <td class="text-end text-danger">{{ formatNumber(soc.blocked_clicks) }}</td>
                  <td class="text-end fw-bold text-coral">{{ formatXaf(soc.earnings_xaf) }}</td>
                </tr>
                <tr v-if="!bySocial.length">
                  <td colspan="4" class="text-center text-muted py-3">Aucune publication sociale enregistrée.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Appareils, Géolocalisation & 14 derniers jours -->
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold text-slate mb-0">
              <i class="bi bi-phone text-coral me-2"></i>Par Appareil
            </h2>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Appareil</th>
                  <th class="text-end">Vues</th>
                  <th class="text-end">Clics</th>
                  <th class="text-end">Gains</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in byDevice" :key="d.device">
                  <td class="text-capitalize fw-medium">{{ d.device }}</td>
                  <td class="text-end">{{ formatNumber(d.impressions) }}</td>
                  <td class="text-end">{{ formatNumber(d.clicks) }}</td>
                  <td class="text-end fw-semibold text-coral">{{ formatXaf(d.earnings_xaf) }}</td>
                </tr>
                <tr v-if="!byDevice.length">
                  <td colspan="4" class="text-center text-muted py-3">Aucune donnée.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold text-slate mb-0">
              <i class="bi bi-geo-alt text-coral me-2"></i>Top Villes (Clics)
            </h2>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Ville / Pays</th>
                  <th class="text-end">Clics</th>
                  <th class="text-end">Gains</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(g, i) in byGeo" :key="i">
                  <td>{{ g.city }} <span class="text-muted small">({{ g.country }})</span></td>
                  <td class="text-end fw-semibold">{{ formatNumber(g.clicks) }}</td>
                  <td class="text-end fw-semibold text-coral">{{ formatXaf(g.earnings_xaf) }}</td>
                </tr>
                <tr v-if="!byGeo.length">
                  <td colspan="3" class="text-center text-muted py-3">Aucun clic géolocalisé.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold text-slate mb-0">
              <i class="bi bi-calendar3 text-coral me-2"></i>14 derniers jours (en direct)
            </h2>
          </div>
          <div class="table-responsive" style="max-height: 260px;">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Date</th>
                  <th class="text-end">Vues</th>
                  <th class="text-end">Clics</th>
                  <th class="text-end">Gains</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="day in [...dailyStats].reverse()" :key="day.date">
                  <td class="small">{{ day.date }}</td>
                  <td class="text-end small">{{ formatNumber(day.impressions) }}</td>
                  <td class="text-end small text-success fw-semibold">{{ formatNumber(day.valid_clicks) }}</td>
                  <td class="text-end small fw-bold text-coral">{{ formatXaf(day.earnings_xaf) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Journal des clics en direct -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white py-3 border-bottom">
        <h2 class="h6 fw-bold text-slate mb-0">
          <i class="bi bi-activity text-coral me-2"></i>20 derniers clics en direct
        </h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Date &amp; Heure</th>
              <th>Publicité</th>
              <th>Source</th>
              <th>Ville / Appareil</th>
              <th>Statut</th>
              <th class="text-end">Gain</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="clk in recentClicks" :key="clk.id">
              <td class="small text-muted">{{ clk.created_at }}</td>
              <td class="fw-semibold">{{ clk.campaign_name }}</td>
              <td class="small">{{ clk.source }}</td>
              <td class="small text-muted">{{ clk.city }} · {{ clk.device_type }}</td>
              <td>
                <span v-if="clk.is_valid" class="badge bg-success-subtle text-success">Valide</span>
                <span v-else class="badge bg-danger-subtle text-danger">Bloqué ({{ clk.invalid_reason || 'fraude' }})</span>
              </td>
              <td class="text-end fw-bold" :class="clk.is_valid ? 'text-coral' : 'text-muted'">
                {{ clk.is_valid ? '+' + formatXaf(clk.earning_xaf) : '0 FCFA' }}
              </td>
            </tr>
            <tr v-if="!recentClicks.length">
              <td colspan="6" class="text-center text-muted py-4">Aucun clic enregistré pour le moment.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PublisherLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.text-coral { color: #ff5c35 !important; }
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
</style>
