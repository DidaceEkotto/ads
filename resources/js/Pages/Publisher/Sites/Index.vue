<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublisherLayout from '../../../Layouts/PublisherLayout.vue';

defineProps({
  sites: { type: Array, required: true },
  categories: { type: Array, required: true },
  formats: { type: Array, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
  name: '',
  domain: '',
  category: 'actualites',
  language: 'fr',
  allowed_formats: ['native', 'square_300x250'],
});

function submit() {
  form.post('/publisher/sites', {
    onSuccess: () => form.reset('name', 'domain'),
  });
}
</script>

<template>
  <PublisherLayout>
    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h1 class="h5 fw-bold mb-3">
              <i class="bi bi-globe2 text-success me-2"></i>Ajouter un site web
            </h1>
            <form @submit.prevent="submit">
              <div class="mb-3">
                <label class="form-label">Nom du site</label>
                <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" required />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Domaine</label>
                <input
                  v-model="form.domain"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.domain }"
                  placeholder="monsite.cm"
                  required
                />
                <div v-if="form.errors.domain" class="invalid-feedback">{{ form.errors.domain }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Catégorie</label>
                <select v-model="form.category" class="form-select">
                  <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Langue principale</label>
                <select v-model="form.language" class="form-select">
                  <option value="fr">Français</option>
                  <option value="en">English</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Types de publicités automatiques</label>
                <div class="d-flex flex-column gap-1">
                  <label v-for="f in formats" :key="f.value" class="form-check small">
                    <input v-model="form.allowed_formats" :value="f.value" type="checkbox" class="form-check-input" />
                    <span class="form-check-label">{{ f.label }}</span>
                  </label>
                </div>
              </div>
              <button type="submit" class="btn btn-coral w-100 fw-semibold" :disabled="form.processing || !form.allowed_formats.length">
                Soumettre le site
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Mes sites web</h2>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Site</th>
                  <th>Catégorie</th>
                  <th>Emplacements</th>
                  <th>Statut</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in sites" :key="s.id">
                  <td>
                    <div class="fw-bold">{{ s.name }}</div>
                    <div class="small text-muted">{{ s.domain }}</div>
                  </td>
                  <td class="text-capitalize small">{{ s.category }} ({{ s.language.toUpperCase() }})</td>
                  <td>
                    <span class="fw-semibold">{{ s.ad_zones_count }}</span>
                    <span v-if="s.auto_ads_enabled" class="badge bg-success-subtle text-success ms-1">Auto</span>
                  </td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'bg-success': s.status === 'approved',
                        'bg-warning text-dark': s.status === 'pending',
                        'bg-danger': s.status === 'rejected' || s.status === 'suspended',
                      }"
                    >
                      {{ s.status_label }}
                    </span>
                  </td>
                  <td class="text-end">
                    <Link :href="`/publisher/sites/${s.id}`" class="btn btn-outline-success btn-sm">
                      Emplacements
                    </Link>
                  </td>
                </tr>
                <tr v-if="!sites.length">
                  <td colspan="5" class="text-center text-muted py-4">Aucun site enregistré.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </PublisherLayout>
</template>
