<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: { type: File, default: null },
  accept: { type: String, default: 'image/jpeg,image/png,image/webp,image/gif' },
  label: { type: String, default: 'Glissez-déposez votre fichier ici ou cliquez pour parcourir' },
  hint: { type: String, default: 'Image < 150 Ko ou GIF < 500 Ko' },
  error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const isDragging = ref(false);
const previewUrl = ref(null);

const fileSizeKb = computed(() =>
  props.modelValue ? (props.modelValue.size / 1024).toFixed(1) : null
);

function handleFile(file) {
  if (!file) return;
  emit('update:modelValue', file);
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = URL.createObjectURL(file);
}

function onInputChange(e) {
  handleFile(e.target.files?.[0]);
}

function onDrop(e) {
  isDragging.value = false;
  handleFile(e.dataTransfer?.files?.[0]);
}

function clearFile() {
  emit('update:modelValue', null);
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
  }
  if (inputRef.value) {
    inputRef.value.value = '';
  }
}
</script>

<template>
  <div>
    <div
      class="dropzone-box rounded-4 p-4 text-center position-relative"
      :class="{
        'is-dragging': isDragging,
        'border-danger': !!error,
      }"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="!modelValue && inputRef?.click()"
    >
      <input
        ref="inputRef"
        type="file"
        :accept="accept"
        class="d-none"
        @change="onInputChange"
      />

      <template v-if="!modelValue">
        <div class="dropzone-icon mx-auto mb-2">
          <i class="bi bi-cloud-arrow-up fs-2"></i>
        </div>
        <div class="fw-semibold text-dark small mb-1">{{ label }}</div>
        <div class="text-muted small">{{ hint }}</div>
      </template>

      <template v-else>
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center gap-3">
          <img
            v-if="previewUrl"
            :src="previewUrl"
            alt="Aperçu"
            class="rounded-3 border object-fit-cover bg-white"
            style="width: 96px; height: 96px;"
          />
          <div class="text-start">
            <div class="fw-bold text-dark small text-truncate" style="max-width: 240px;">
              {{ modelValue.name }}
            </div>
            <div class="small text-muted mb-2">{{ fileSizeKb }} Ko</div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-coral" @click.stop="inputRef?.click()">
                Changer
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" @click.stop="clearFile">
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
  </div>
</template>

<style scoped>
.dropzone-box {
  border: 2px dashed #dee2e6;
  background-color: #fffaf8;
  cursor: pointer;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}
.dropzone-box:hover,
.dropzone-box.is-dragging {
  border-color: #ff5c35;
  background-color: #fff1ed;
}
.dropzone-icon {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background-color: #fff1ed;
  color: #ff5c35;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-outline-coral {
  border-color: #ff5c35;
  color: #ff5c35;
}
.btn-outline-coral:hover {
  background-color: #ff5c35;
  color: #fff;
}
</style>
