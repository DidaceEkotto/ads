<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

const props = defineProps({
  wallet: { type: Object, required: true },
  methods: { type: Array, required: true },
  payouts: { type: Object, required: true },
  minPayoutXaf: { type: Number, required: true },
  earningsHoldDays: { type: Number, default: 14 },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const selectedProofPayout = ref(null);

const form = useForm({
  payment_method_id: props.methods.find((m) => m.is_default)?.id ?? props.methods[0]?.id ?? '',
  amount_xaf: props.minPayoutXaf,
});

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function submit() {
  form.post('/publisher/payouts');
}
</script>

<template>
  <PublisherLayout>
    <div v-if="flashSuccess" class="alert alert-success">{{ flashSuccess }}</div>
    <div v-if="flashError" class="alert alert-danger">{{ flashError }}</div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body">
            <div class="text-muted small">Disponible pour retrait</div>
            <div class="fs-2 fw-bold text-coral">{{ formatXaf(wallet.available_balance_xaf) }}</div>
            <div class="small text-muted">En attente ({{ earningsHoldDays }} j) : {{ formatXaf(wallet.pending_balance_xaf) }}</div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body">
            <h1 class="h5 fw-bold text-slate mb-3">Demander un retrait</h1>
            <form @submit.prevent="submit">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Compte Mobile Money</label>
                <select v-model="form.payment_method_id" class="form-select" required>
                  <option value="" disabled>Sélectionner un compte</option>
                  <option v-for="m in methods" :key="m.id" :value="m.id">{{ m.label }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Montant (FCFA)</label>
                <input v-model.number="form.amount_xaf" type="number" :min="minPayoutXaf" class="form-control" required />
                <div class="form-text">Minimum : {{ formatXaf(minPayoutXaf) }}</div>
              </div>
              <button type="submit" class="btn btn-coral w-100 fw-semibold" :disabled="form.processing || !methods.length">
                Demander le retrait
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white border-0 pt-4 px-4 fw-bold text-slate">Historique des retraits</div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>Date</th><th>Compte</th><th>Montant</th><th>Statut</th><th class="text-end">Preuve / Reçu</th></tr>
              </thead>
              <tbody>
                <tr v-for="p in payouts.data" :key="p.id">
                  <td class="small text-muted">{{ p.created_at }}</td>
                  <td class="small">
                    <div>{{ p.method_label }}</div>
                    <div v-if="p.gateway_reference" class="text-muted">Réf : <code>{{ p.gateway_reference }}</code></div>
                  </td>
                  <td class="fw-bold">{{ formatXaf(p.amount_xaf) }}</td>
                  <td>
                    <span class="badge" :class="{
                      'bg-success': p.status === 'paid',
                      'bg-warning text-dark': p.status === 'pending' || p.status === 'processing',
                      'bg-danger': p.status === 'rejected',
                    }">{{ p.status_label }}</span>
                  </td>
                  <td class="text-end">
                    <button
                      v-if="p.proof_url"
                      type="button"
                      class="btn btn-outline-primary btn-sm me-1"
                      @click="selectedProofPayout = p"
                    >
                      <i class="bi bi-image me-1"></i>Capture
                    </button>
                    <a
                      v-if="p.status === 'paid'"
                      :href="p.receipt_url"
                      target="_blank"
                      class="btn btn-outline-dark btn-sm"
                    >
                      <i class="bi bi-file-earmark-text me-1"></i>Reçu
                    </a>
                  </td>
                </tr>
                <tr v-if="!payouts.data.length"><td colspan="5" class="text-center text-muted py-4">Aucun retrait.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'affichage de la capture de preuve -->
    <div v-if="selectedProofPayout" class="modal fade show d-block" tabindex="-1" style="background: rgba(17, 28, 38, 0.55);" @click.self="selectedProofPayout = null">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">Preuve de paiement — {{ formatXaf(selectedProofPayout.amount_xaf) }}</h5>
            <button type="button" class="btn-close" @click="selectedProofPayout = null"></button>
          </div>
          <div class="modal-body text-center">
            <img :src="selectedProofPayout.proof_url" alt="Capture de retrait" class="img-fluid rounded-3 border mb-3" />
            <div v-if="selectedProofPayout.gateway_reference" class="small text-muted">
              ID Transaction : <code>{{ selectedProofPayout.gateway_reference }}</code>
            </div>
            <div v-if="selectedProofPayout.admin_note" class="small text-muted mt-1">
              {{ selectedProofPayout.admin_note }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </PublisherLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.text-coral { color: #ff5c35 !important; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover:not(:disabled) { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
