<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdvertiserLayout from '../../../Layouts/AdvertiserLayout.vue';

defineProps({
  campaigns: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function toggleCampaign(id) {
  router.patch(`/advertiser/campaigns/${id}/toggle`, {}, { preserveScroll: true });
}

function submitForReview(id) {
  router.patch(`/advertiser/campaigns/${id}/submit`, {}, { preserveScroll: true });
}
</script>

<template>
  <AdvertiserLayout>
    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <div v-if="flashError" class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ flashError }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 fw-bold mb-0">
        <i class="bi bi-megaphone me-2 text-primary"></i>Mes Campagnes
      </h1>
      <Link href="/advertiser/campaigns/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nouvelle campagne
      </Link>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Campagne</th>
              <th>Type / Modèle</th>
              <th>Budget / Dépensé</th>
              <th>Durée &amp; Arrêt</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in campaigns.data" :key="c.id">
              <td>
                <div class="fw-bold">{{ c.name }}</div>
                <div class="small text-muted">Créée le {{ c.created_at }}</div>
              </td>
              <td>
                <span class="badge bg-secondary me-1">{{ c.type_label }}</span>
                <span class="small text-muted">{{ c.billing_model.toUpperCase() }} ({{ formatXaf(c.unit_price_xaf) }})</span>
              </td>
              <td>
                <div class="fw-semibold">{{ formatXaf(c.spent_xaf) }} / {{ formatXaf(c.total_budget_xaf) }}</div>
                <div class="progress mt-1" style="height: 6px;">
                  <div
                    class="progress-bar"
                    :style="{ width: Math.min(100, (c.spent_xaf / Math.max(1, c.total_budget_xaf)) * 100) + '%' }"
                  ></div>
                </div>
              </td>
              <td>
                <div v-if="c.ends_at" class="small">
                  <div class="fw-semibold text-dark">
                    <i class="bi bi-calendar-event me-1 text-coral"></i>{{ c.ends_at }}
                  </div>
                  <div class="text-muted">
                    Durée : {{ c.duration_days }} j ·
                    <span v-if="c.is_expired" class="text-danger fw-medium">Arrêtée</span>
                    <span v-else class="text-success fw-medium">{{ c.days_remaining }} j restants</span>
                  </div>
                </div>
                <span v-else class="text-muted small">—</span>
              </td>
              <td>
                <span
                  class="badge"
                  :class="{
                    'bg-success': c.status === 'active',
                    'bg-warning text-dark': c.status === 'pending_review' || c.status === 'paused',
                    'bg-secondary': c.status === 'draft' || c.status === 'completed',
                    'bg-danger': c.status === 'rejected',
                  }"
                >
                  {{ c.status_label }}
                </span>
              </td>
              <td class="text-end">
                <Link :href="`/advertiser/campaigns/${c.id}`" class="btn btn-outline-dark btn-sm me-1">
                  <i class="bi bi-bar-chart-line me-1"></i>Détails
                </Link>
                <Link :href="`/advertiser/campaigns/${c.id}/edit`" class="btn btn-outline-secondary btn-sm me-1">
                  <i class="bi bi-pencil me-1"></i>Modifier
                </Link>
                <button
                  v-if="c.status === 'draft' || c.status === 'rejected'"
                  type="button"
                  class="btn btn-outline-primary btn-sm me-1"
                  @click="submitForReview(c.id)"
                >
                  Soumettre
                </button>
                <button
                  v-if="c.status === 'active' || c.status === 'paused'"
                  type="button"
                  class="btn btn-sm"
                  :class="c.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success'"
                  @click="toggleCampaign(c.id)"
                >
                  {{ c.status === 'active' ? 'Mettre en pause' : 'Reprendre' }}
                </button>
              </td>
            </tr>
            <tr v-if="!campaigns.data.length">
              <td colspan="6" class="text-center text-muted py-4">
                Aucune campagne créée. Lancez votre première campagne !
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdvertiserLayout>
</template>

<style scoped>
.text-coral { color: #ff5c35 !important; }
</style>
