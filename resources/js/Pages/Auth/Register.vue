<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LocaleSwitcher from '../../Components/LocaleSwitcher.vue';
import { useTranslation } from '../../i18n/useTranslation';

const { t } = useTranslation();
const page = usePage();
const siteSettings = computed(() => page.props.siteSettings || {});
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
  name: '',
  email: '',
  phone: '+237',
  role: 'advertiser',
  city: '',
  company_name: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
});

const passwordRules = computed(() => {
  const pwd = form.password || '';
  return [
    { key: 'length', label: 'Minimum 8 caractères', valid: pwd.length >= 8 },
    { key: 'upper', label: 'Au moins une lettre majuscule (A-Z)', valid: /[A-Z]/.test(pwd) },
    { key: 'number', label: 'Au moins un chiffre (0-9)', valid: /[0-9]/.test(pwd) },
    { key: 'special', label: 'Au moins un caractère spécial (@, _, -)', valid: /[@_-]/.test(pwd) },
  ];
});

const passwordScore = computed(() => passwordRules.value.filter((r) => r.valid).length);

const strengthMeta = computed(() => {
  if (!form.password) {
    return { percent: 0, barClass: 'bg-secondary', label: '', textClass: 'text-muted' };
  }
  return matchStrength(passwordScore.value);
});

function matchStrength(score) {
  if (score <= 1) return { percent: 25, barClass: 'bg-danger', label: 'Faible', textClass: 'text-danger' };
  if (score === 2) return { percent: 50, barClass: 'bg-warning', label: 'Moyen', textClass: 'text-warning' };
  if (score === 3) return { percent: 75, barClass: 'bg-info', label: 'Bon', textClass: 'text-info' };
  return { percent: 100, barClass: 'bg-success', label: 'Sécurisé', textClass: 'text-success' };
}

function submit() {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>

<template>
  <div class="container-fluid p-0 min-vh-100 d-flex">
    <div class="row g-0 flex-grow-1 w-100">
      <!-- Partie 1 : Image + dégradé + texte (en bas sur mobile, à gauche sur desktop) -->
      <div class="col-lg-5 order-2 order-lg-1 position-relative auth-visual-panel">
        <img
          src="/images/hero-creator.jpg"
          alt="Rejoindre CM-Ads Cameroun"
          class="auth-bg-img"
        />
        <div class="auth-gradient-overlay p-4 p-lg-5 d-flex flex-column justify-content-between text-white h-100">
          <div>
            <span class="badge rounded-pill bg-white bg-opacity-20 border border-white border-opacity-25 px-3 py-2">
              <i class="bi bi-stars text-warning me-1"></i>Inscription gratuite
            </span>
          </div>
          <div class="my-auto py-4 py-lg-0">
            <h2 class="display-6 fw-bold mb-3">
              Rejoignez le réseau publicitaire N°1 au Cameroun.
            </h2>
            <p class="text-white-75 mb-4">
              Que vous souhaitiez promouvoir votre entreprise à Douala &amp; Yaoundé ou monétiser votre site et vos réseaux sociaux, tout commence ici.
            </p>
            <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
              <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Portefeuille FCFA (XAF) sécurisé</li>
              <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Recharges &amp; retraits MTN MoMo / Orange Money</li>
              <li><i class="bi bi-check-circle-fill text-warning me-2"></i>Support client direct sur WhatsApp</li>
            </ul>
          </div>
          <div class="small text-white-50">
            &copy; {{ new Date().getFullYear() }} CM-Ads Cameroun
          </div>
        </div>
      </div>

      <!-- Partie 2 : Formulaire fullscreen (en premier sur mobile, à droite sur desktop) -->
      <div class="col-lg-7 order-1 order-lg-2 bg-white p-4 p-md-5 d-flex flex-column justify-content-between min-vh-100-lg">
        <!-- Barre discrète : Retour accueil & Langue -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <Link href="/" class="btn btn-light btn-sm rounded-pill px-3 text-slate fw-medium border">
            <i class="bi bi-arrow-left me-1"></i>Accueil
          </Link>
          <LocaleSwitcher />
        </div>

        <div class="mx-auto w-100 my-auto" style="max-width: 580px;">
          <div class="mb-4">
            <Link href="/" class="text-decoration-none d-inline-flex align-items-center fw-bold fs-4 text-slate mb-2">
              <img v-if="siteSettings.logo_url" :src="siteSettings.logo_url" :alt="siteSettings.name" class="me-2" style="max-height: 36px; object-fit: contain;" />
              <template v-else>
                <span class="hs-logo-mark me-2"><i class="bi bi-megaphone-fill"></i></span>
                CM<span class="text-coral">-Ads</span>
              </template>
            </Link>
            <h1 class="h3 fw-bold text-slate mb-1">{{ t('register') }}</h1>
            <p class="text-muted small mb-0">{{ t('tagline') }}</p>
          </div>

          <form @submit.prevent="submit">
            <div class="mb-3">
              <label class="form-label fw-medium small">{{ t('account_type') }}</label>
              <div class="row g-2">
                <div class="col-sm-6">
                  <label
                    class="border rounded-3 p-3 d-flex align-items-center gap-2 cursor-pointer h-100 role-card"
                    :class="{ 'border-coral bg-coral-subtle': form.role === 'advertiser' }"
                  >
                    <input v-model="form.role" type="radio" value="advertiser" class="form-check-input mt-0" />
                    <span class="small fw-semibold">
                      <i class="bi bi-megaphone text-coral me-1"></i>{{ t('advertiser') }}
                    </span>
                  </label>
                </div>
                <div class="col-sm-6">
                  <label
                    class="border rounded-3 p-3 d-flex align-items-center gap-2 cursor-pointer h-100 role-card"
                    :class="{ 'border-coral bg-coral-subtle': form.role === 'publisher' }"
                  >
                    <input v-model="form.role" type="radio" value="publisher" class="form-check-input mt-0" />
                    <span class="small fw-semibold">
                      <i class="bi bi-globe2 text-success me-1"></i>{{ t('publisher') }}
                    </span>
                  </label>
                </div>
              </div>
              <div v-if="form.errors.role" class="text-danger small mt-1">{{ form.errors.role }}</div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-medium">{{ t('full_name') }}</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.name }"
                  required
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium">{{ t('phone_cm') }}</label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.phone }"
                  placeholder="+2376XXXXXXXX"
                  required
                />
                <div v-if="form.errors.phone" class="invalid-feedback">{{ form.errors.phone }}</div>
              </div>

              <div class="col-12">
                <label class="form-label small fw-medium">{{ t('email') }}</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.email }"
                  required
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium">{{ t('city') }}</label>
                <input v-model="form.city" type="text" class="form-control" />
              </div>

              <div v-if="form.role === 'advertiser'" class="col-md-6">
                <label class="form-label small fw-medium">{{ t('company_name') }}</label>
                <input v-model="form.company_name" type="text" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium">{{ t('password') }}</label>
                <div class="input-group">
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password }"
                    required
                  />
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :aria-label="showPassword ? 'Masquer' : 'Afficher'"
                    @click="showPassword = !showPassword"
                  >
                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                  </button>
                  <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-medium">{{ t('password_confirm') }}</label>
                <div class="input-group">
                  <input
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    class="form-control"
                    required
                  />
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :aria-label="showPasswordConfirm ? 'Masquer' : 'Afficher'"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                  >
                    <i class="bi" :class="showPasswordConfirm ? 'bi-eye-slash' : 'bi-eye'"></i>
                  </button>
                </div>
              </div>

              <!-- Indicateur de complexité du mot de passe -->
              <div class="col-12">
                <div class="p-3 rounded-3 bg-light border">
                  <div class="d-flex justify-content-between align-items-center mb-1 small">
                    <span class="fw-medium text-muted">Sécurité du mot de passe</span>
                    <span v-if="strengthMeta.label" class="fw-bold" :class="strengthMeta.textClass">
                      {{ strengthMeta.label }}
                    </span>
                  </div>
                  <div class="progress mb-2" style="height: 7px;">
                    <div
                      class="progress-bar transition-all"
                      :class="strengthMeta.barClass"
                      role="progressbar"
                      :style="{ width: strengthMeta.percent + '%' }"
                      :aria-valuenow="strengthMeta.percent"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="row g-1 small">
                    <div v-for="rule in passwordRules" :key="rule.key" class="col-sm-6">
                      <div class="d-flex align-items-center gap-1" :class="rule.valid ? 'text-success fw-medium' : 'text-muted'">
                        <i class="bi" :class="rule.valid ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                        <span>{{ rule.label }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-check my-3">
              <input
                id="accept_terms"
                v-model="form.accept_terms"
                type="checkbox"
                class="form-check-input"
                :class="{ 'is-invalid': form.errors.accept_terms }"
              />
              <label for="accept_terms" class="form-check-label small text-muted">{{ t('accept_terms') }}</label>
              <div v-if="form.errors.accept_terms" class="invalid-feedback">{{ form.errors.accept_terms }}</div>
            </div>

            <button type="submit" class="btn btn-coral btn-lg w-100 fw-semibold" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
              {{ t('register') }}
            </button>
          </form>

          <hr class="my-4" />
          <div class="text-center small text-muted">
            {{ t('already_registered') }}
            <Link href="/login" class="fw-semibold text-coral text-decoration-none">{{ t('login') }}</Link>
          </div>
        </div>

        <div></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-slate { color: #192733; }
.text-coral { color: #ff5c35 !important; }
.border-coral { border-color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }
.text-white-75 { color: rgba(255, 255, 255, 0.85); }
.cursor-pointer { cursor: pointer; }

.hs-logo-mark {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background-color: #ff5c35;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.btn-coral {
  background-color: #ff5c35;
  border-color: #ff5c35;
  color: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}
.btn-coral:hover {
  background-color: #e64922;
  border-color: #e64922;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(255, 92, 53, 0.3);
}

.role-card {
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.auth-visual-panel {
  min-height: 360px;
}
.auth-bg-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.auth-gradient-overlay {
  position: relative;
  z-index: 2;
  background: linear-gradient(165deg, rgba(25, 39, 51, 0.4) 0%, rgba(25, 39, 51, 0.72) 45%, rgba(17, 28, 38, 0.94) 100%);
}

@media (min-width: 992px) {
  .min-vh-100-lg {
    min-height: 100vh;
  }
}
</style>
