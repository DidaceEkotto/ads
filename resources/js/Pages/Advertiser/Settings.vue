<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FileDropzone from '../../Components/FileDropzone.vue';
import AdvertiserLayout from '../../Layouts/AdvertiserLayout.vue';

const props = defineProps({
  profile: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
  company_name: props.profile.company_name ?? '',
  business_sector: props.profile.business_sector ?? '',
  city: props.profile.city ?? '',
  postal_box: props.profile.postal_box ?? '',
  whatsapp_number: props.profile.whatsapp_number ?? '',
  website_url: props.profile.website_url ?? '',
  facebook_url: props.profile.facebook_url ?? '',
  linkedin_url: props.profile.linkedin_url ?? '',
  x_url: props.profile.x_url ?? '',
  description: props.profile.description ?? '',
  logo: null,
});

function onLogoChange(e) {
  const file = e.target.files[0] ?? null;
  form.logo = file;
  if (file) {
    previewUrl.value = URL.createObjectURL(file);
  }
}

function submit() {
  form.post('/advertiser/settings', {
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

    <div class="mb-4">
      <h1 class="h3 fw-bold text-slate mb-1">Paramètres de l'entreprise</h1>
      <p class="text-muted small mb-0">Complétez la fiche de votre entreprise (logo, boîte postale, réseaux sociaux).</p>
    </div>

    <form class="row g-4" @submit.prevent="submit">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div v-if="profile.logo_url && !form.logo" class="text-center mb-3">
            <img
              :src="profile.logo_url"
              alt="Logo actuel"
              class="rounded-4 border object-fit-contain bg-light"
              style="width: 110px; height: 110px;"
            />
          </div>
          <label class="form-label small fw-semibold">Logo de l'entreprise</label>
          <FileDropzone
            v-model="form.logo"
            accept="image/jpeg,image/png,image/webp"
            label="Glissez-déposez votre logo ou cliquez"
            hint="JPG, PNG ou WebP • Max 300 Ko"
            :error="form.errors.logo"
          />
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Nom de l'entreprise</label>
              <input v-model="form.company_name" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Secteur d'activité</label>
              <input v-model="form.business_sector" type="text" class="form-control" placeholder="ex: E-commerce, Tech, Restauration" />
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Ville</label>
              <input v-model="form.city" type="text" class="form-control" placeholder="Douala" />
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Boîte postale</label>
              <input v-model="form.postal_box" type="text" class="form-control" placeholder="BP 1234 Douala" />
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">WhatsApp entreprise</label>
              <input v-model="form.whatsapp_number" type="tel" class="form-control" placeholder="+2376XXXXXXXX" />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Site web</label>
              <input v-model="form.website_url" type="url" class="form-control" placeholder="https://monsite.cm" :class="{ 'is-invalid': form.errors.website_url }" />
              <div v-if="form.errors.website_url" class="invalid-feedback">{{ form.errors.website_url }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Page Facebook</label>
              <input v-model="form.facebook_url" type="url" class="form-control" placeholder="https://facebook.com/..." />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">LinkedIn</label>
              <input v-model="form.linkedin_url" type="url" class="form-control" placeholder="https://linkedin.com/company/..." />
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Compte X (Twitter)</label>
              <input v-model="form.x_url" type="url" class="form-control" placeholder="https://x.com/..." />
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Description de l'entreprise</label>
              <textarea v-model="form.description" rows="4" class="form-control" placeholder="Présentez votre activité..."></textarea>
            </div>
          </div>

          <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-coral fw-semibold px-4" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
              Enregistrer les paramètres
            </button>
          </div>
        </div>
      </div>
    </form>
  </AdvertiserLayout>
</template>

<style scoped>
.text-slate { color: #192733; }
.btn-coral { background-color: #ff5c35; border-color: #ff5c35; color: #fff; }
.btn-coral:hover { background-color: #e64922; border-color: #e64922; color: #fff; }
</style>
