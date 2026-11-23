<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import { useTranslation } from '../i18n/useTranslation';

defineProps({
  dashboardUrl: { type: String, default: null },
});

const { t } = useTranslation();
const page = usePage();
const supportWhatsapp = computed(() => page.props.supportWhatsapp);
const demoEnabled = computed(() => page.props.demoEnabled);
const siteSettings = computed(() => page.props.siteSettings || {});

const activeTab = ref('advertisers');

const caseStudies = {
  advertisers: {
    image: '/images/hero-advertiser.jpg',
    quote: '« En ciblant uniquement Douala et Yaoundé en CPC à 50 FCFA, notre boutique a doublé ses visites qualifiées tout en bloquant 100 % des clics de robots. »',
    author: 'Marc T., Responsable Marketing — Douala',
    stat1Value: '+148 %',
    stat1Label: 'de trafic qualifié sur mobile',
    stat2Value: '0 FCFA',
    stat2Label: 'facturé sur les clics suspects',
  },
  publishers: {
    image: '/images/hero-publisher.jpg',
    quote: '« L\'intégration du format natif sur notre portail d\'actualités nous a permis de monétiser même les lecteurs équipés de bloqueurs, avec des retraits rapides sur MTN MoMo. »',
    author: 'Clarisse N., Éditrice Web — Yaoundé',
    stat1Value: '70 %',
    stat1Label: 'reversés sur chaque clic valide',
    stat2Value: '48 h',
    stat2Label: 'délai moyen de traitement des retraits',
  },
  creators: {
    image: '/images/hero-creator.jpg',
    quote: '« Avec les liens courts /c/, les aperçus Facebook s\'affichent parfaitement et chaque clic réel de notre communauté est comptabilisé dans notre portefeuille. »',
    author: 'Kevin B., Créateur Tech — Bafoussam',
    stat1Value: '4,6 %',
    stat1Label: 'taux de clic moyen sur posts',
    stat2Value: '5 000 FCFA',
    stat2Label: 'seuil minimum de retrait',
  },
};

onMounted(() => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );

  document.querySelectorAll('.reveal-on-scroll').forEach((el) => observer.observe(el));
});
</script>

<template>
  <div class="hs-page text-dark">
    <!-- Barre utilitaire supérieure -->
    <div class="bg-white border-bottom py-1 small d-none d-lg-block">
      <div class="container d-flex justify-content-between align-items-center text-muted">
        <div class="d-flex align-items-center gap-3">
          <span><i class="bi bi-geo-alt text-coral me-1"></i>Cameroun (XAF / FCFA)</span>
          <a :href="supportWhatsapp" target="_blank" rel="noopener" class="text-decoration-none text-muted hover-coral">
            <i class="bi bi-whatsapp text-success me-1"></i>{{ t('support_whatsapp') }}
          </a>
        </div>
        <div class="d-flex align-items-center gap-3">
          <LocaleSwitcher />
          <Link v-if="demoEnabled" href="/demo" class="text-decoration-none text-dark fw-semibold hover-coral">
            <i class="bi bi-play-circle text-coral me-1"></i>Démo Sandbox
          </Link>
        </div>
      </div>
    </div>

    <!-- Navbar principale -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white bg-opacity-95 backdrop-blur sticky-top border-bottom py-3">
      <div class="container">
        <Link class="navbar-brand fw-bold fs-4 d-flex align-items-center text-slate" href="/">
          <img v-if="siteSettings.logo_url" :src="siteSettings.logo_url" :alt="siteSettings.name" class="me-2" style="max-height: 36px; object-fit: contain;" />
          <template v-else>
            <span class="hs-logo-mark me-2"><i class="bi bi-megaphone-fill"></i></span>
            CM<span class="text-coral">-Ads</span>
          </template>
        </Link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#hsNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div id="hsNav" class="collapse navbar-collapse">
          <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0 gap-lg-3 fw-medium">
            <li class="nav-item"><a class="nav-link text-slate hover-coral" href="#modules">Produits</a></li>
            <li class="nav-item"><a class="nav-link text-slate hover-coral" href="#protection">Anti-fraude</a></li>
            <li class="nav-item"><a class="nav-link text-slate hover-coral" href="#results">Résultats</a></li>
            <li class="nav-item"><a class="nav-link text-slate hover-coral" href="#ecosystem">Paiements</a></li>
          </ul>

          <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-lg-none me-1"><LocaleSwitcher /></div>
            <template v-if="dashboardUrl">
              <a :href="dashboardUrl" class="btn btn-coral fw-semibold px-4 btn-glow">
                <i class="bi bi-speedometer2 me-1"></i>{{ t('dashboard') }}
              </a>
            </template>
            <template v-else>
              <Link href="/register" class="btn btn-coral fw-semibold px-3 btn-glow">Commencer gratuitement</Link>
              <Link href="/login" class="btn btn-outline-coral fw-semibold px-3">{{ t('login') }}</Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero centré avec animations d'entrée & 3 photos éditoriales -->
    <header class="bg-cream pt-5 pb-5 border-bottom position-relative overflow-hidden">
      <div class="hero-orb hero-orb-1"></div>
      <div class="hero-orb hero-orb-2"></div>

      <div class="container text-center pt-lg-3 position-relative">
        <div class="hero-anim hero-delay-1 text-uppercase fw-bold small letter-spacing text-coral mb-2">
          <i class="bi bi-stars me-1"></i>{{ t('landing_badge') }}
        </div>
        <h1 class="hero-anim hero-delay-2 display-4 fw-bold text-slate mx-auto mb-3 hs-hero-title">
          {{ t('landing_hero_title') }}
        </h1>
        <p class="hero-anim hero-delay-3 lead text-muted mx-auto mb-4 hs-hero-sub">
          {{ t('landing_hero_sub') }}
        </p>
        <div class="hero-anim hero-delay-4 d-flex justify-content-center flex-wrap gap-3 mb-5">
          <Link href="/register" class="btn btn-coral btn-lg fw-semibold px-4 py-3 btn-glow">
            {{ t('landing_cta_adv') }} <i class="bi bi-arrow-right ms-1"></i>
          </Link>
          <Link href="/register" class="btn btn-outline-coral btn-lg fw-semibold px-4 py-3 bg-white">
            {{ t('landing_cta_pub') }}
          </Link>
        </div>

        <!-- 3 cartes photo éditoriales type HubSpot -->
        <div class="row g-4 text-start hero-anim hero-delay-5">
          <div class="col-md-4">
            <div class="hs-photo-card rounded-4 overflow-hidden position-relative shadow-sm">
              <img src="/images/hero-publisher.jpg" alt="Éditeur web au Cameroun" class="hs-photo-img" />
              <div class="hs-photo-overlay p-4 d-flex flex-column justify-content-between text-white">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="glass-pill"><i class="bi bi-window-sidebar me-1"></i>Sites Web .cm</span>
                  <span class="glass-stat">+70 % rev.</span>
                </div>
                <div>
                  <h2 class="h5 fw-bold mb-1">Monétisez vos médias locaux</h2>
                  <p class="small text-white-75 mb-0">Bannières légères (&lt; 150 Ko) et formats natifs intégrés en 1 ligne de code.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="hs-photo-card rounded-4 overflow-hidden position-relative shadow-sm">
              <img src="/images/hero-creator.jpg" alt="Créateur de contenu Cameroun" class="hs-photo-img" />
              <div class="hs-photo-overlay p-4 d-flex flex-column justify-content-between text-white">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="glass-pill"><i class="bi bi-share-fill me-1"></i>Facebook • X • LinkedIn</span>
                  <span class="glass-stat">Liens /c/</span>
                </div>
                <div>
                  <h2 class="h5 fw-bold mb-1">Activez les créateurs sociaux</h2>
                  <p class="small text-white-75 mb-0">Aperçus OpenGraph parfaits et filtrage automatique des bots d'aperçu.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="hs-photo-card rounded-4 overflow-hidden position-relative shadow-sm">
              <img src="/images/hero-advertiser.jpg" alt="Annonceur et entrepreneur au Cameroun" class="hs-photo-img" />
              <div class="hs-photo-overlay p-4 d-flex flex-column justify-content-between text-white">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="glass-pill"><i class="bi bi-phone-vibrate me-1"></i>SasPay • MoMo &amp; OM</span>
                  <span class="glass-stat">100 % XAF</span>
                </div>
                <div>
                  <h2 class="h5 fw-bold mb-1">Pilotez votre ROI en FCFA</h2>
                  <p class="small text-white-75 mb-0">Ciblage par ville, contrôle anti-fraude et paiements Mobile Money instantanés.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Section "Qu'est-ce que CM-Ads ?" + Bandeau confiance -->
    <section class="py-5 bg-white border-bottom reveal-on-scroll">
      <div class="container py-3">
        <div class="row align-items-center g-4 mb-5">
          <div class="col-lg-5">
            <h2 class="h2 fw-bold text-slate mb-0">Qu'est-ce que CM-Ads ?</h2>
          </div>
          <div class="col-lg-7">
            <p class="text-muted mb-0 fs-5 lh-base">
              CM-Ads est la régie publicitaire et plateforme d'affiliation conçue pour le Cameroun. Elle réunit annonceurs, éditeurs de sites web et créateurs sur les réseaux sociaux autour d'un suivi transparent en FCFA et d'une protection anti-fraude rigoureuse.
            </p>
          </div>
        </div>

        <div class="border-top pt-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
          <span class="small fw-bold text-muted text-uppercase">Écosystème natif camerounais</span>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="eco-pill"><i class="bi bi-phone text-warning me-1"></i>MTN MoMo</span>
            <span class="eco-pill"><i class="bi bi-phone text-coral me-1"></i>Orange Money</span>
            <span class="eco-pill"><i class="bi bi-shield-lock text-primary me-1"></i>SasPay</span>
            <span class="eco-pill"><i class="bi bi-facebook text-primary me-1"></i>Facebook</span>
            <span class="eco-pill"><i class="bi bi-linkedin text-info me-1"></i>LinkedIn</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Colonne gauche fixe + Grille 2 colonnes -->
    <section id="modules" class="py-5 bg-cream">
      <div class="container py-4">
        <div class="row g-5">
          <div class="col-lg-4 reveal-on-scroll">
            <div class="sticky-lg-top" style="top: 100px;">
              <div class="text-coral fw-bold small text-uppercase mb-2">Plateforme unifiée</div>
              <h2 class="h2 fw-bold text-slate mb-3">
                Acquérir des clients est exigeant. CM-Ads vous simplifie la tâche.
              </h2>
              <p class="text-muted mb-4">
                Chaque module est pensé pour les connexions mobiles camerounaises, la transparence financière (ledger) et le contrôle total de votre budget.
              </p>
              <div class="d-flex flex-wrap gap-2">
                <Link href="/register" class="btn btn-coral fw-semibold px-3 btn-glow">Créer un compte</Link>
                <a href="#results" class="btn btn-outline-coral bg-white fw-semibold px-3">Voir les résultats</a>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="row g-4 reveal-on-scroll">
              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-bullseye"></i></span>
                    <h3 class="h5 fw-bold mb-0">Campagnes CPC &amp; CPM</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Budget global et journalier en FCFA</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Pause et reprise en un clic</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Lancer une campagne <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-geo-alt-fill"></i></span>
                    <h3 class="h5 fw-bold mb-0">Ciblage Local Cameroun</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Douala, Yaoundé, Bafoussam, Garoua…</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Langue (FR/EN), appareil &amp; catégorie</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Configurer mon ciblage <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-code-slash"></i></span>
                    <h3 class="h5 fw-bold mb-0">Diffusion Web &amp; Natif</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Script neutre sans mots-clés bloqués</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Images &lt; 150 Ko et lazy-loading</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Ajouter mon site <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-share"></i></span>
                    <h3 class="h5 fw-bold mb-0">Posts Sociaux Sponsorisés</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Lien court <code>/c/code</code> dédié par créateur</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Mention #pub et preuve par URL</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Monétiser mes réseaux <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-bar-chart-line"></i></span>
                    <h3 class="h5 fw-bold mb-0">Statistiques Temps Réel</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Impressions, clics, CTR, taux de blocage</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Alertes automatiques de budget bas</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Voir le tableau de bord <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 hs-module-card">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="hs-icon-sm bg-coral-subtle text-coral"><i class="bi bi-wallet2"></i></span>
                    <h3 class="h5 fw-bold mb-0">Grand Livre &amp; SasPay</h3>
                  </div>
                  <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-3">
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Paiements idempotents et webhooks signés</li>
                    <li><i class="bi bi-check-lg text-coral me-1"></i>Répartition 70 % éditeur / 30 % plateforme</li>
                  </ul>
                  <Link href="/register" class="small fw-semibold text-coral text-decoration-none mt-auto">
                    Découvrir le portefeuille <i class="bi bi-arrow-right ms-1"></i>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Dégradé Pêche/Corail -->
    <section id="protection" class="hs-peach-gradient py-5 reveal-on-scroll">
      <div class="container py-4">
        <div class="row align-items-end g-3 mb-5">
          <div class="col-lg-6">
            <h2 class="h2 fw-bold text-slate mb-0">
              Une protection anti-fraude qui veille sur votre budget 24/7.
            </h2>
          </div>
          <div class="col-lg-6 text-lg-end">
            <p class="text-slate opacity-75 mb-0">
              Chaque clic passe par notre moteur de scoring avant toute facturation annonceur ou crédit éditeur.
            </p>
          </div>
        </div>

        <div class="row g-4 align-items-stretch">
          <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white bg-opacity-75 hs-module-card">
              <div class="badge bg-coral text-white align-self-start mb-3">Score 95/100</div>
              <h3 class="h5 fw-bold">Filtrage des bots d'aperçu</h3>
              <p class="small text-muted mb-0">
                <code>facebookexternalhit</code>, <code>Twitterbot</code> et <code>LinkedInBot</code> reçoivent une belle carte OpenGraph sans jamais déclencher de facturation.
              </p>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card border-0 shadow-lg rounded-4 p-4 h-100 bg-white float-card">
              <div class="badge bg-dark text-white align-self-start mb-3">Empreinte 24 h</div>
              <h3 class="h5 fw-bold">Zéro clic dupliqué</h3>
              <p class="small text-muted mb-0">
                Empreinte visiteur (IP + User-Agent), limitation de débit et blocage strict des auto-clics éditeur. Les clics invalides sont tracés pour l'admin.
              </p>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white bg-opacity-75 hs-module-card">
              <div class="badge bg-success text-white align-self-start mb-3">Maturité 7–30 j</div>
              <h3 class="h5 fw-bold">Gains sécurisés</h3>
              <p class="small text-muted mb-0">
                Les revenus éditeurs restent en statut « en attente » durant la période de contrôle avant d'être débloqués pour retrait Mobile Money.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Résultats avec onglets interactifs + photo -->
    <section id="results" class="py-5 bg-white reveal-on-scroll">
      <div class="container py-4">
        <div class="row align-items-center mb-4">
          <div class="col-lg-8">
            <div class="text-coral fw-bold small text-uppercase mb-1">Cas d'usage au Cameroun</div>
            <h2 class="h2 fw-bold text-slate mb-0">Des résultats concrets pour chaque acteur.</h2>
          </div>
        </div>

        <ul class="nav nav-tabs mb-4">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link fw-semibold"
              :class="activeTab === 'advertisers' ? 'active text-coral' : 'text-muted'"
              @click="activeTab = 'advertisers'"
            >
              Annonceurs &amp; PME
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link fw-semibold"
              :class="activeTab === 'publishers' ? 'active text-coral' : 'text-muted'"
              @click="activeTab = 'publishers'"
            >
              Éditeurs Web
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link fw-semibold"
              :class="activeTab === 'creators' ? 'active text-coral' : 'text-muted'"
              @click="activeTab = 'creators'"
            >
              Créateurs Sociaux
            </button>
          </li>
        </ul>

        <Transition name="fade-slide" mode="out-in">
          <div :key="activeTab" class="card border rounded-4 p-4 p-lg-5 shadow-sm overflow-hidden">
            <div class="row align-items-center g-4">
              <div class="col-lg-4">
                <img
                  :src="caseStudies[activeTab].image"
                  alt="Témoignage CM-Ads"
                  class="img-fluid rounded-4 shadow-sm w-100"
                  style="height: 220px; object-fit: cover;"
                />
              </div>
              <div class="col-lg-4">
                <p class="fs-5 fst-italic text-slate mb-3">{{ caseStudies[activeTab].quote }}</p>
                <div class="fw-bold text-muted small">{{ caseStudies[activeTab].author }}</div>
              </div>
              <div class="col-lg-4">
                <div class="row g-3 text-center">
                  <div class="col-6">
                    <div class="bg-cream rounded-3 p-3">
                      <div class="fs-3 fw-bold text-coral">{{ caseStudies[activeTab].stat1Value }}</div>
                      <div class="small text-muted">{{ caseStudies[activeTab].stat1Label }}</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="bg-cream rounded-3 p-3">
                      <div class="fs-3 fw-bold text-slate">{{ caseStudies[activeTab].stat2Value }}</div>
                      <div class="small text-muted">{{ caseStudies[activeTab].stat2Label }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </section>

    <!-- Bandeau Dark Slate pré-footer -->
    <section id="ecosystem" class="bg-slate text-white py-5 reveal-on-scroll">
      <div class="container py-4">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <h2 class="display-6 fw-bold mb-3">
              Faites décoller vos campagnes et revenus au Cameroun, en toute simplicité.
            </h2>
            <p class="text-white-50 mb-0">
              Inscription gratuite. Rechargez ou retirez en FCFA via SasPay, MTN MoMo et Orange Money.
            </p>
          </div>
          <div class="col-lg-5 d-flex flex-wrap gap-3 justify-content-lg-end">
            <Link href="/register" class="btn btn-coral btn-lg fw-semibold px-4 btn-glow">Commencer gratuitement</Link>
            <a :href="supportWhatsapp" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg fw-semibold px-4">
              <i class="bi bi-whatsapp me-2"></i>Support WhatsApp
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer riche -->
    <footer class="bg-slate-deep text-white-50 pt-5 pb-4 small">
      <div class="container">
        <div class="row g-4 pb-4 border-bottom border-secondary border-opacity-25">
          <div class="col-md-4">
            <div class="fw-bold fs-5 text-white mb-2">
              CM<span class="text-coral">-Ads</span>
            </div>
            <p class="mb-0">
              Plateforme publicitaire et d'affiliation dédiée au marché camerounais. Paiements sécurisés en FCFA (XAF).
            </p>
          </div>
          <div class="col-6 col-md-2">
            <div class="fw-bold text-white mb-2">Annonceurs</div>
            <ul class="list-unstyled d-flex flex-column gap-1">
              <li><Link href="/register" class="text-decoration-none text-white-50">Créer une campagne</Link></li>
              <li><a href="#modules" class="text-decoration-none text-white-50">Ciblage local</a></li>
              <li><a href="#protection" class="text-decoration-none text-white-50">Anti-fraude</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-bold text-white mb-2">Éditeurs &amp; Créateurs</div>
            <ul class="list-unstyled d-flex flex-column gap-1">
              <li><Link href="/register" class="text-decoration-none text-white-50">Ajouter un site web</Link></li>
              <li><a href="#modules" class="text-decoration-none text-white-50">Posts sponsorisés</a></li>
              <li><a href="#ecosystem" class="text-decoration-none text-white-50">Retraits MoMo / OM</a></li>
            </ul>
          </div>
          <div class="col-md-3">
            <div class="fw-bold text-white mb-2">Conformité &amp; Aide</div>
            <ul class="list-unstyled d-flex flex-column gap-1">
              <li><Link href="/terms" class="text-decoration-none text-white-50">{{ t('terms_title') }}</Link></li>
              <li><Link href="/privacy" class="text-decoration-none text-white-50">{{ t('privacy_title') }}</Link></li>
              <li>
                <a :href="supportWhatsapp" target="_blank" rel="noopener" class="text-decoration-none text-success fw-semibold">
                  <i class="bi bi-whatsapp me-1"></i>{{ t('support_whatsapp') }}
                </a>
              </li>
            </ul>
          </div>
        </div>
        <div class="pt-3 d-flex flex-wrap justify-content-between align-items-center">
          <span>&copy; {{ new Date().getFullYear() }} CM-Ads Cameroun.</span>
          <span>Laravel 13 • Inertia • Vue 3 • SasPay</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.text-coral { color: #ff5c35 !important; }
.bg-coral { background-color: #ff5c35 !important; }
.bg-coral-subtle { background-color: #fff1ed !important; }
.bg-cream { background-color: #fef4ea !important; }
.text-slate { color: #192733 !important; }
.bg-slate { background-color: #192733 !important; }
.bg-slate-deep { background-color: #111c26 !important; }
.letter-spacing { letter-spacing: 0.08em; }
.backdrop-blur { backdrop-filter: blur(10px); }
.text-white-75 { color: rgba(255, 255, 255, 0.85); }

.hover-coral { transition: color 0.2s ease; }
.hover-coral:hover { color: #ff5c35 !important; }

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
  transform: translateY(-2px);
}
.btn-glow:hover {
  box-shadow: 0 10px 25px rgba(255, 92, 53, 0.35);
}
.btn-outline-coral {
  border-color: #ff5c35;
  color: #ff5c35;
  transition: all 0.2s ease;
}
.btn-outline-coral:hover {
  background-color: #ff5c35;
  color: #fff;
  transform: translateY(-2px);
}

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

.hs-hero-title { max-width: 840px; }
.hs-hero-sub { max-width: 660px; }

/* Orbes lumineuses en arrière-plan du Hero */
.hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  pointer-events: none;
  opacity: 0.45;
  animation: orbFloat 10s ease-in-out infinite alternate;
}
.hero-orb-1 {
  width: 320px;
  height: 320px;
  background: #ffd6c9;
  top: -80px;
  left: -80px;
}
.hero-orb-2 {
  width: 360px;
  height: 360px;
  background: #ffe8d6;
  bottom: 40px;
  right: -100px;
  animation-delay: 2s;
}

/* Cartes photo éditoriales avec zoom doux au survol */
.hs-photo-card {
  height: 310px;
  cursor: pointer;
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease;
}
.hs-photo-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 1.5rem 3rem rgba(25, 39, 51, 0.22) !important;
}
.hs-photo-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}
.hs-photo-card:hover .hs-photo-img {
  transform: scale(1.07);
}
.hs-photo-overlay {
  position: relative;
  z-index: 2;
  height: 100%;
  background: linear-gradient(180deg, rgba(17, 28, 38, 0.25) 0%, rgba(17, 28, 38, 0.45) 45%, rgba(17, 28, 38, 0.92) 100%);
}
.glass-pill {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
}
.glass-stat {
  background: rgba(255, 92, 53, 0.9);
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
}

.eco-pill {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 999px;
  padding: 0.45rem 0.95rem;
  font-size: 0.88rem;
  font-weight: 600;
  transition: transform 0.2s ease, border-color 0.2s ease;
}
.eco-pill:hover {
  transform: translateY(-2px);
  border-color: #ff5c35;
}

.hs-icon-sm {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.hs-module-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.hs-module-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 1rem 2rem rgba(25, 39, 51, 0.09) !important;
}

.hs-peach-gradient {
  background: linear-gradient(135deg, #ffe8d6 0%, #ffd6c9 50%, #ffc4b8 100%);
}

.float-card {
  animation: floatSlow 5s ease-in-out infinite;
}

/* Animations d'entrée du Hero */
.hero-anim {
  opacity: 0;
  transform: translateY(22px);
  animation: fadeUp 0.75s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.hero-delay-1 { animation-delay: 0.05s; }
.hero-delay-2 { animation-delay: 0.15s; }
.hero-delay-3 { animation-delay: 0.25s; }
.hero-delay-4 { animation-delay: 0.35s; }
.hero-delay-5 { animation-delay: 0.48s; }

/* Révélation fluide au scroll */
.reveal-on-scroll {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1), transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}
.reveal-on-scroll.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Transition des onglets Résultats */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.28s ease, transform 0.28s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes fadeUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes floatSlow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@keyframes orbFloat {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(25px, -20px) scale(1.08); }
}

@media (prefers-reduced-motion: reduce) {
  .hero-anim, .reveal-on-scroll, .float-card, .hero-orb {
    animation: none !important;
    transition: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
