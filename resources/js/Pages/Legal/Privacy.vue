<script setup>
import AuthLayout from '../../Layouts/AuthLayout.vue';
import { useTranslation } from '../../i18n/useTranslation';

const { t } = useTranslation();
</script>

<template>
  <AuthLayout>
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 fw-bold mb-3">{{ t('privacy_title') }}</h1>
        <p class="text-muted">
          Politique de protection des données personnelles (empreintes techniques anonymisées, numéros Mobile Money,
          conformité avec la législation camerounaise sur la protection des données).
        </p>
      </div>
    </div>
  </AuthLayout>
</template>
