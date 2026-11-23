<script setup>
import { router } from '@inertiajs/vue3';
import { useTranslation } from '../i18n/useTranslation';

const { locale } = useTranslation();

function switchLocale(lang) {
  if (lang === locale.value) return;
  router.post(`/locale/${lang}`, {}, { preserveScroll: true });
}
</script>

<template>
  <div class="dropdown">
    <button
      class="btn btn-sm btn-light border dropdown-toggle fw-medium d-inline-flex align-items-center gap-1"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <i class="bi bi-translate text-muted"></i>
      <span class="text-uppercase">{{ locale }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
      <li>
        <button
          type="button"
          class="dropdown-item d-flex justify-content-between align-items-center"
          :class="{ active: locale === 'fr' }"
          @click="switchLocale('fr')"
        >
          <span>Français (FR)</span>
          <i v-if="locale === 'fr'" class="bi bi-check2 ms-2"></i>
        </button>
      </li>
      <li>
        <button
          type="button"
          class="dropdown-item d-flex justify-content-between align-items-center"
          :class="{ active: locale === 'en' }"
          @click="switchLocale('en')"
        >
          <span>English (EN)</span>
          <i v-if="locale === 'en'" class="bi bi-check2 ms-2"></i>
        </button>
      </li>
    </ul>
  </div>
</template>
