<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LocaleSwitcher from '../../Components/LocaleSwitcher.vue';
import { useTranslation } from '../../i18n/useTranslation';

const { t } = useTranslation();
const page = usePage();
const siteSettings = computed(() => page.props.siteSettings || {});
const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

function submit() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div class="container-fluid p-0 min-vh-100 d-flex">
    <div class="row g-0 flex-grow-1 w-100">
      <!-- Partie 1 : Image + dégradé + texte (en bas sur mobile, à gauche sur desktop) -->
      <div class="col-lg-6 order-2 order-lg-1 position-relative auth-visual-panel">
        <img
          src="/images/hero-advertiser.jpg"
          alt="CM-Ads Cameroun"
          class="auth-bg-img"
        />
        <div class="auth-gradient-overlay p-4 p-lg-5 d-flex flex-column justify-content-between text-white h-100">
          <div>
            <span class="badge rounded-pill bg-white bg-opacity-20 border border-white border-opacity-25 px-3 py-2">
              <i class="bi bi-shield-check text-warning me-1"></i>CM-Ads Cameroun
            </span>
          </div>
          <div class="my-auto py-4 py-lg-0">
            <h2 class="display-6 fw-bold mb-3">
              Pilotez vos campagnes et revenus en FCFA en temps réel.
            </h2>
            <p class="text-white-75 fs-5 mb-4">
              Accédez à votre tableau de bord Annonceur ou Éditeur, suivez vos clics valides et gérez vos paiements MTN MoMo &amp; Orange Money via SasPay.
            </p>
            <div class="d-flex flex-wrap gap-4 small">
              <span><i class="bi bi-check-circle-fill text-warning me-1"></i>70 % reversement éditeur</span>
              <span><i class="bi bi-check-circle-fill text-warning me-1"></i>Anti-fraude 24/7</span>
            </div>
          </div>
          <div class="small text-white-50">
            &copy; {{ new Date().getFullYear() }} CM-Ads Cameroun
          </div>
        </div>
      </div>

      <!-- Partie 2 : Formulaire fullscreen (en premier sur mobile, à droite sur desktop) -->
      <div class="col-lg-6 order-1 order-lg-2 bg-white p-4 p-md-5 d-flex flex-column justify-content-between min-vh-100-lg">
        <!-- Barre discrète : Retour accueil & Langue -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <Link href="/" class="btn btn-light btn-sm rounded-pill px-3 text-slate fw-medium border">
            <i class="bi bi-arrow-left me-1"></i>Accueil
          </Link>
          <LocaleSwitcher />
        </div>

        <div class="mx-auto w-100 my-auto" style="max-width: 440px;">
          <div class="mb-4">
            <Link href="/" class="text-decoration-none d-inline-flex align-items-center fw-bold fs-4 text-slate mb-3">
              <img v-if="siteSettings.logo_url" :src="siteSettings.logo_url" :alt="siteSettings.name" class="me-2" style="max-height: 36px; object-fit: contain;" />
              <template v-else>
                <span class="hs-logo-mark me-2"><i class="bi bi-megaphone-fill"></i></span>
                CM<span class="text-coral">-Ads</span>
              </template>
            </Link>
            <h1 class="h3 fw-bold text-slate mb-1">{{ t('login') }}</h1>
            <p class="text-muted small mb-0">{{ t('tagline') }}</p>
          </div>

          <form @submit.prevent="submit">
            <div class="mb-3">
              <label for="email" class="form-label fw-medium small">{{ t('email') }}</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-control form-control-lg"
                :class="{ 'is-invalid': form.errors.email }"
                placeholder="nom@exemple.cm"
                required
                autofocus
              />
              <div v-if="form.errors.email" class="invalid-feedback">
                {{ form.errors.email }}
              </div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label fw-medium small">{{ t('password') }}</label>
              <div class="input-group input-group-lg">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.password }"
                  placeholder="••••••••"
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
                <div v-if="form.errors.password" class="invalid-feedback">
                  {{ form.errors.password }}
                </div>
              </div>
            </div>

            <div class="mb-4 form-check">
              <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input" />
              <label for="remember" class="form-check-label small text-muted">{{ t('remember_me') }}</label>
            </div>

            <button type="submit" class="btn btn-coral btn-lg w-100 fw-semibold" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
              {{ t('login') }}
            </button>
          </form>

          <hr class="my-4" />
          <div class="text-center small text-muted">
            {{ t('no_account') }}
            <Link href="/register" class="fw-semibold text-coral text-decoration-none">{{ t('register') }}</Link>
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
.text-white-75 { color: rgba(255, 255, 255, 0.85); }

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
