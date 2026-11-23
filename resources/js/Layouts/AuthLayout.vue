<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import { useTranslation } from '../i18n/useTranslation';

const { t } = useTranslation();
const page = usePage();
const supportWhatsapp = computed(() => page.props.supportWhatsapp);
</script>

<template>
  <div class="min-vh-100 d-flex flex-column bg-light">
    <nav class="navbar navbar-light bg-white border-bottom py-2">
      <div class="container">
        <Link class="navbar-brand fw-bold text-primary" href="/">
          <i class="bi bi-megaphone-fill me-2"></i>{{ t('app_name') }}
        </Link>
        <div class="d-flex align-items-center gap-2">
          <a
            :href="supportWhatsapp"
            target="_blank"
            rel="noopener"
            class="btn btn-outline-success btn-sm"
          >
            <i class="bi bi-whatsapp me-1"></i>{{ t('support_whatsapp') }}
          </a>
          <LocaleSwitcher />
        </div>
      </div>
    </nav>

    <main class="flex-grow-1 d-flex align-items-center py-4">
      <div class="container">
        <slot />
      </div>
    </main>

    <footer class="bg-white border-top py-3 text-center small text-muted">
      <div class="container d-flex flex-wrap justify-content-center gap-3">
        <span>&copy; {{ new Date().getFullYear() }} CM-Ads</span>
        <Link href="/terms" class="text-decoration-none">{{ t('terms_title') }}</Link>
        <Link href="/privacy" class="text-decoration-none">{{ t('privacy_title') }}</Link>
      </div>
    </footer>
  </div>
</template>
