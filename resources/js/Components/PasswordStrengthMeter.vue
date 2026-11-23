<script setup>
import { computed } from 'vue';

const props = defineProps({
  password: { type: String, default: '' },
});

const rules = computed(() => {
  const pwd = props.password || '';
  return [
    { key: 'length', label: 'Minimum 8 caractères', valid: pwd.length >= 8 },
    { key: 'upper', label: 'Au moins une lettre majuscule (A-Z)', valid: /[A-Z]/.test(pwd) },
    { key: 'number', label: 'Au moins un chiffre (0-9)', valid: /[0-9]/.test(pwd) },
    { key: 'special', label: 'Au moins un caractère spécial (@, _, -)', valid: /[@_-]/.test(pwd) },
  ];
});

const score = computed(() => rules.value.filter((r) => r.valid).length);

const meta = computed(() => {
  if (!props.password) {
    return { percent: 0, barClass: 'bg-secondary', label: '', textClass: 'text-muted' };
  }
  if (score.value <= 1) return { percent: 25, barClass: 'bg-danger', label: 'Faible', textClass: 'text-danger' };
  if (score.value === 2) return { percent: 50, barClass: 'bg-warning', label: 'Moyen', textClass: 'text-warning' };
  if (score.value === 3) return { percent: 75, barClass: 'bg-info', label: 'Bon', textClass: 'text-info' };
  return { percent: 100, barClass: 'bg-success', label: 'Sécurisé', textClass: 'text-success' };
});
</script>

<template>
  <div class="p-3 rounded-3 bg-light border">
    <div class="d-flex justify-content-between align-items-center mb-1 small">
      <span class="fw-medium text-muted">Sécurité du mot de passe</span>
      <span v-if="meta.label" class="fw-bold" :class="meta.textClass">{{ meta.label }}</span>
    </div>
    <div class="progress mb-2" style="height: 7px;">
      <div
        class="progress-bar"
        :class="meta.barClass"
        role="progressbar"
        :style="{ width: meta.percent + '%', transition: 'width 0.25s ease' }"
        :aria-valuenow="meta.percent"
        aria-valuemin="0"
        aria-valuemax="100"
      ></div>
    </div>
    <div class="row g-1 small">
      <div v-for="rule in rules" :key="rule.key" class="col-sm-6">
        <div class="d-flex align-items-center gap-1" :class="rule.valid ? 'text-success fw-medium' : 'text-muted'">
          <i class="bi" :class="rule.valid ? 'bi-check-circle-fill' : 'bi-circle'"></i>
          <span>{{ rule.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
