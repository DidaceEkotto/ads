<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FileDropzone from '../../Components/FileDropzone.vue';
import AdvertiserLayout from '../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  user: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const previewAvatar = ref(props.user.avatar_url);

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone,
  avatar: null,
  remove_avatar: false,
});

function onAvatarChange(e) {
  const file = e.target.files[0] ?? null;
  form.avatar = file;
  form.remove_avatar = false;
  if (file) {
    previewAvatar.value = URL.createObjectURL(file);
  }
}

function removeAvatar() {
  form.avatar = null;
  form.remove_avatar = true;
  previewAvatar.value = null;
}

function submit() {
  form.post('/advertiser/profile', {
    forceFormData: true,
    preserveScroll: true,
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
        <h1 class="h3 fw-bold text-slate mb-1">Mon profil</h1>
        <p class="text-muted small mb-0">Gérez vos informations personnelles et votre photo de profil.</p>
      </div>
      <Link href="/advertiser/password" class="btn btn-outline-secondary btn-sm fw-semibold">
        <i class="bi bi-shield-lock me-1"></i>Modifier le mot de passe
      </Link>
    </div>

    <form class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 720px;" @submit.prevent="submit">
      <div class="mb-4 pb-4 border-bottom">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img
            v-if="user.avatar_url && !form.remove_avatar"
            :src="user.avatar_url"
            alt="Photo de profil"
            class="rounded-circle object-fit-cover border"
            style="width: 64px; height: 64px;"
          />
          <div
            v-else
            class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4"
            style="width: 64px; height: 64px; background: linear-gradient(135deg, #ff5c35, #192733);"
          >
            {{ user.initials }}
          </div>
          <div>
            <div class="fw-semibold small">Photo de profil</div>
            <div class="small text-muted">Affichée dans la barre supérieure à la place des initiales.</div>
            <button v-if="user.avatar_url && !form.remove_avatar" type="button" class="btn btn-link btn-sm text-danger p-0" @click="removeAvatar">
              Supprimer la photo actuelle
            </button>
          </div>
        </div>
        <FileDropzone
          v-model="form.avatar"
          accept="image/jpeg,image/png,image/webp"
          label="Glissez-déposez votre photo de profil ou cliquez"
          hint="JPG, PNG ou WebP • Max 300 Ko"
          :error="form.errors.avatar"
          @update:model-value="form.remove_avatar = false"
        />
      </div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label small fw-semibold">Nom complet</label>
          <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" required />
          <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Adresse e-mail</label>
          <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" required />
          <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Téléphone (+237...)</label>
          <input v-model="form.phone" type="tel" class="form-control" :class="{ 'is-invalid': form.errors.phone }" required />
          <div v-if="form.errors.phone" class="invalid-feedback">{{ form.errors.phone }}</div>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-coral fw-semibold px-4" :disabled="form.processing">
          <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
          Enregistrer mon profil
        </button>
      </div>
    </form>
  </AdvertiserLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
