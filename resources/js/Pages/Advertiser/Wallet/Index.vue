<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdvertiserLayout from '../../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  wallet: { type: Object, required: true },
  transactions: { type: Object, required: true },
  minDepositXaf: { type: Number, required: true },
  initialIdempotencyKey: { type: String, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm({
  amount_xaf: Math.max(5000, props.minDepositXaf),
  idempotency_key: props.initialIdempotencyKey,
});

function formatXaf(amount) {
  return new Intl.NumberFormat('fr-FR').format(amount ?? 0) + ' FCFA';
}

function submitDeposit() {
  form.post('/advertiser/wallet/deposit');
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

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="text-muted small">Solde disponible</div>
            <div class="fs-2 fw-bold text-coral">{{ formatXaf(wallet.available_balance_xaf) }}</div>
            <div class="small text-muted mt-1">
              Total dépensé : {{ formatXaf(wallet.total_spent_xaf) }}
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h2 class="h5 fw-bold mb-3">
              <i class="bi bi-plus-circle text-coral me-2"></i>Recharger via SasPay
            </h2>
            <p class="small text-muted">
              Paiement sécurisé par MTN MoMo ou Orange Money via SasPay.
            </p>
            <form @submit.prevent="submitDeposit">
              <div class="mb-3">
                <label class="form-label">Montant (FCFA)</label>
                <input
                  v-model.number="form.amount_xaf"
                  type="number"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.amount_xaf }"
                  :min="minDepositXaf"
                  step="500"
                  required
                />
                <div v-if="form.errors.amount_xaf" class="invalid-feedback">
                  {{ form.errors.amount_xaf }}
                </div>
                <div class="form-text">Minimum : {{ formatXaf(minDepositXaf) }}</div>
              </div>

              <button type="submit" class="btn btn-coral w-100 fw-semibold" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                Payer avec SasPay
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">
              <i class="bi bi-journal-text me-2"></i>Grand livre des opérations (Ledger)
            </h2>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Montant</th>
                  <th>Solde après</th>
                  <th>Statut</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tx in transactions.data" :key="tx.id">
                  <td class="small text-muted">{{ tx.created_at }}</td>
                  <td>
                    <div class="fw-semibold">{{ tx.type_label }}</div>
                    <div class="small text-muted">{{ tx.description }}</div>
                  </td>
                  <td :class="tx.direction === 'credit' ? 'text-success fw-bold' : 'text-danger fw-bold'">
                    {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatXaf(tx.amount_xaf) }}
                  </td>
                  <td class="small">{{ formatXaf(tx.balance_after_xaf) }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'bg-success': tx.status === 'completed',
                        'bg-warning text-dark': tx.status === 'pending',
                        'bg-danger': tx.status === 'failed' || tx.status === 'cancelled',
                      }"
                    >
                      {{ tx.status }}
                    </span>
                  </td>
                  <td class="text-end">
                    <a v-if="tx.invoice_url" :href="tx.invoice_url" target="_blank" class="btn btn-outline-dark btn-sm">
                      <i class="bi bi-file-earmark-text"></i>
                    </a>
                  </td>
                </tr>
                <tr v-if="!transactions.data.length">
                  <td colspan="6" class="text-center text-muted py-4">
                    Aucune opération enregistrée pour le moment.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AdvertiserLayout>
</template>

<style scoped>
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
.btn-coral:disabled {
  background-color: #ff5c35;
  border-color: #ff5c35;
  opacity: 0.65;
  color: #fff;
}
</style>
