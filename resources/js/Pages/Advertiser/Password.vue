<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PasswordStrengthMeter from '../../Components/PasswordStrengthMeter.vue';
import AdvertiserLayout from '../../Layouts/AdvertiserLayout.vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function submit() {
  form.put('/advertiser/password', {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <AdvertiserLayout>
    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle me-2"></i>{{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold text-slate mb-1">Modifier mon mot de passe</h1>
        <p class="text-muted small mb-0">Renforcez la sécurité de votre compte annonceur.</p>
      </div>
      <Link href="/advertiser/profile" class="btn btn-outline-secondary btn-sm">Retour au profil</Link>
    </div>

    <form class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 620px;" @submit.prevent="submit">
      <div class="mb-3">
        <label class="form-label small fw-semibold">Mot de passe actuel</label>
        <div class="input-group">
          <input
            v-model="form.current_password"
            :type="showCurrent ? 'text' : 'password'"
            class="form-control"
            :class="{ 'is-invalid': form.errors.current_password }"
            required
          />
          <button type="button" class="btn btn-outline-secondary" @click="showCurrent = !showCurrent">
            <i class="bi" :class="showCurrent ? 'bi-eye-slash' : 'bi-eye'"></i>
          </button>
          <div v-if="form.errors.current_password" class="invalid-feedback">{{ form.errors.current_password }}</div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Nouveau mot de passe</label>
        <div class="input-group">
          <input
            v-model="form.password"
            :type="showNew ? 'text' : 'password'"
            class="form-control"
            :class="{ 'is-invalid': form.errors.password }"
            required
          />
          <button type="button" class="btn btn-outline-secondary" @click="showNew = !showNew">
            <i class="bi" :class="showNew ? 'bi-eye-slash' : 'bi-eye'"></i>
          </button>
          <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Confirmer le nouveau mot de passe</label>
        <div class="input-group">
          <input
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            class="form-control"
            required
          />
          <button type="button" class="btn btn-outline-secondary" @click="showConfirm = !showConfirm">
            <i class="bi" :class="showConfirm ? 'bi-eye-slash' : 'bi-eye'"></i>
          </button>
        </div>
      </div>

      <div class="mb-4">
        <PasswordStrengthMeter :password="form.password" />
      </div>

      <button type="submit" class="btn btn-coral fw-semibold px-4" :disabled="form.processing">
        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
        Mettre à jour le mot de passe
      </button>
    </form>
  </AdvertiserLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
