<script setup>
import AuthLayout from '../../Layouts/AuthLayout.vue';
import { useTranslation } from '../../i18n/useTranslation';

const { t } = useTranslation();
</script>

<template>
  <AuthLayout>
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 fw-bold mb-3">{{ t('terms_title') }}</h1>
        <p class="text-muted">
          Conditions générales d'utilisation de la plateforme publicitaire CM-Ads, conformes au droit camerounais
          (Loi n° 2010/012 relative à la cybersécurité et la cybercriminalité et Loi n° 2010/021 régissant le commerce électronique).
        </p>
      </div>
    </div>
  </AuthLayout>
</template>
