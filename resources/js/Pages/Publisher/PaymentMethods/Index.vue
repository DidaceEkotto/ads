<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

defineProps({
  methods: { type: Array, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
  provider: 'mtn_momo',
  account_number: '+237',
  account_name: '',
  is_default: true,
});

function submit() {
  form.post('/publisher/payment-methods', {
    onSuccess: () => form.reset('account_name'),
  });
}

function setDefault(id) {
  router.patch(`/publisher/payment-methods/${id}/default`);
}

function removeMethod(id) {
  router.delete(`/publisher/payment-methods/${id}`);
}
</script>

<template>
  <PublisherLayout>
    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h1 class="h5 fw-bold mb-3">
              <i class="bi bi-phone me-2 text-success"></i>Ajouter un compte Mobile Money
            </h1>
            <form @submit.prevent="submit">
              <div class="mb-3">
                <label class="form-label">Opérateur</label>
                <select v-model="form.provider" class="form-select">
                  <option value="mtn_momo">MTN Mobile Money</option>
                  <option value="orange_money">Orange Money</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Numéro (+237...)</label>
                <input
                  v-model="form.account_number"
                  type="tel"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.account_number }"
                  placeholder="+2376XXXXXXXX"
                  required
                />
                <div v-if="form.errors.account_number" class="invalid-feedback">
                  {{ form.errors.account_number }}
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Nom du titulaire</label>
                <input
                  v-model="form.account_name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.account_name }"
                  required
                />
                <div v-if="form.errors.account_name" class="invalid-feedback">
                  {{ form.errors.account_name }}
                </div>
              </div>
              <div class="form-check mb-3">
                <input id="is_default" v-model="form.is_default" type="checkbox" class="form-check-input" />
                <label for="is_default" class="form-check-label">Définir par défaut</label>
              </div>
              <button type="submit" class="btn btn-success w-100" :disabled="form.processing">
                Enregistrer
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Mes comptes de retrait</h2>
          </div>
          <div class="list-group list-group-flush">
            <div
              v-for="m in methods"
              :key="m.id"
              class="list-group-item d-flex justify-content-between align-items-center"
            >
              <div>
                <div class="fw-bold">
                  {{ m.provider_label }}
                  <span v-if="m.is_default" class="badge bg-success ms-2">Par défaut</span>
                </div>
                <div class="small text-muted">{{ m.account_number }} — {{ m.account_name }}</div>
              </div>
              <div class="d-flex gap-2">
                <button
                  v-if="!m.is_default"
                  type="button"
                  class="btn btn-outline-success btn-sm"
                  @click="setDefault(m.id)"
                >
                  Par défaut
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeMethod(m.id)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
            <div v-if="!methods.length" class="list-group-item text-center text-muted py-4">
              Aucun compte Mobile Money enregistré.
            </div>
          </div>
        </div>
      </div>
    </div>
  </PublisherLayout>
</template>
