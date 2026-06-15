<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import DestinationTabs from '@/Components/DestinationTabs.vue';
import AuthRequiredModal from '@/Components/AuthRequiredModal.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  featuredDestinations: {
    type: Array,
    default: () => [],
  },
  featuredPlans: {
    type: Array,
    default: () => [],
  },
  destinationGroups: {
    type: Object,
    default: () => ({}),
  },
  content: {
    type: Object,
    default: () => ({}),
  },
});

const trustItems = [
  {
    title: 'GLOBAL COVERAGE',
    text: 'Stay connected across Canada, USA & 190 worldwide destinations.',
    image: '/images/blipblap/Group-22-300x259.png',
  },
  {
    title: 'INSTANT ACTIVATION',
    text: 'No physical SIM, just scan the QR and start.',
    image: '/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-02-300x300.png',
  },
  {
    title: 'AFFORDABLE & TRANSPARENT',
    text: 'No hidden fees, no roaming surprises.',
    image: '/images/blipblap/trust-transparent.svg',
  },
  {
    title: '24/7 SUPPORT',
    text: 'We are here wherever you need help.',
    image: '/images/blipblap/trust-support-24-7.svg',
  },
];

const homepageContent = computed(() => props.content || {});
const heroEyebrow = computed(() => homepageContent.value.hero_eyebrow || 'Complete eSIM Connectivity Platform');
const heroTitle = computed(() => homepageContent.value.hero_title || 'Blip Blap Fast, Reliable ESIM');
const heroText = computed(() => homepageContent.value.hero_text || 'Instant Canada & Global eSIM plans, activate in seconds with zero hassle. Connect across 190+ countries with affordable data and 24/7 support.');
const heroCtaLabel = computed(() => homepageContent.value.hero_cta_label || 'Explore eSIM Plans');
const heroCtaUrl = computed(() => homepageContent.value.hero_cta_url || '/esim-plans');
const heroImageUrl = computed(() => homepageContent.value.hero_image_url || '/images/blipblap/trust-icon.webp');
const contentTrustItems = computed(() => homepageContent.value.trust_items?.length ? homepageContent.value.trust_items : trustItems);
const promoBanners = computed(() => (homepageContent.value.promo_banners || []).filter((item) => item?.title || item?.text));
const faqHeading = computed(() => homepageContent.value.faq_heading || 'Our FAQs Are A Great Place To Find Answers Quickly.');
const faqIntro = computed(() => homepageContent.value.faq_intro || 'A compilation of questions and answers that will help you decide.');

const activeTab = ref('Top Destinations');
const activeStep = ref(0);
const activeFeaturedPlanIndex = ref(0);
const featuredDragStartX = ref(0);
const featuredDragDeltaX = ref(0);
const featuredDragging = ref(false);
const featuredSwipeHandled = ref(false);
const page = usePage();
const authPromptCheckoutUrl = ref('');
const isLoggedIn = computed(() => Boolean(page.props.auth.user));

const fallbackGroups = {
  'Top Destinations': props.featuredDestinations,
  'Local eSIMs': props.featuredDestinations,
  'Regional Packs': [],
  'Worldwide eSIMs': [],
};

const groups = computed(() => ({
  ...fallbackGroups,
  ...props.destinationGroups,
}));

const visibleDestinations = computed(() => (groups.value[activeTab.value] || []).slice(0, 8));

const planDataLabel = (plan) => {
  if (plan.unlimited) {
    return 'Unlimited';
  }

  return `${Number(plan.data_amount || 0)} ${plan.data_unit || 'GB'}`;
};

const planLocation = (plan) => plan.country_name || plan.region_name || 'Global';

const destinationUrl = (name) => {
  const slug = name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');

  if (name.toLowerCase().startsWith('global')) {
    return '/global-esim';
  }

  return `/destinations/${slug}`;
};

const destinationMeta = (destination) => {
  const parts = [];

  if (destination.min_price) {
    parts.push(`from ${destination.currency || 'USD'} ${Number(destination.min_price).toFixed(2)}`);
  }

  if (destination.plan_count) {
    parts.push(`${destination.plan_count} plans`);
  }

  return parts.join(' · ');
};

const requestCheckout = (event, plan) => {
  if (isLoggedIn.value) {
    return;
  }

  event.preventDefault();
  authPromptCheckoutUrl.value = `/checkout/${plan.slug}`;
};

const featuredPlanState = (index) => {
  const count = props.featuredPlans.length;

  if (count <= 1) {
    return {
      'is-active': index === activeFeaturedPlanIndex.value,
    };
  }

  const previousIndex = (activeFeaturedPlanIndex.value - 1 + count) % count;
  const nextIndex = (activeFeaturedPlanIndex.value + 1) % count;

  return {
    'is-active': index === activeFeaturedPlanIndex.value,
    'is-previous': index === previousIndex,
    'is-next': index === nextIndex,
  };
};

const advanceFeaturedPlan = (direction = 1) => {
  const count = props.featuredPlans.length;

  if (count <= 1) {
    return;
  }

  activeFeaturedPlanIndex.value = (activeFeaturedPlanIndex.value + direction + count) % count;
};

const resetFeaturedPlanTimer = () => {
  window.clearInterval(featuredPlanTimer);

  featuredPlanTimer = window.setInterval(() => {
    advanceFeaturedPlan(1);
  }, 5000);
};

const moveFeaturedPlan = (direction) => {
  advanceFeaturedPlan(direction);
  resetFeaturedPlanTimer();
};

const startFeaturedDrag = (event) => {
  if (props.featuredPlans.length <= 1) {
    return;
  }

  featuredDragging.value = true;
  featuredSwipeHandled.value = false;
  featuredDragDeltaX.value = 0;
  featuredDragStartX.value = event.clientX ?? event.touches?.[0]?.clientX ?? 0;
};

const updateFeaturedDrag = (event) => {
  if (!featuredDragging.value) {
    return;
  }

  const currentX = event.clientX ?? event.touches?.[0]?.clientX ?? featuredDragStartX.value;
  featuredDragDeltaX.value = currentX - featuredDragStartX.value;
};

const finishFeaturedDrag = () => {
  if (!featuredDragging.value) {
    return;
  }

  const threshold = 42;
  const delta = featuredDragDeltaX.value;

  featuredDragging.value = false;

  if (Math.abs(delta) < threshold) {
    featuredDragDeltaX.value = 0;
    return;
  }

  featuredSwipeHandled.value = true;
  moveFeaturedPlan(delta < 0 ? 1 : -1);
  featuredDragDeltaX.value = 0;
};

const handleFeaturedPlanClick = (event, plan) => {
  if (featuredSwipeHandled.value) {
    event.preventDefault();
    featuredSwipeHandled.value = false;
    return;
  }

  requestCheckout(event, plan);
};

const steps = [
  {
    title: 'Choose Your eSIM Plan',
    text: 'Select your destination and data package.',
    image: '/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-01-300x300.png',
    alt: 'Choose an eSIM plan',
  },
  {
    title: 'Scan the QR Code',
    text: 'Instant eSIM installation, no physical SIM needed.',
    image: '/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-02-300x300.png',
    alt: 'Scan the eSIM QR code',
  },
  {
    title: 'Connect & Enjoy',
    text: 'Turn on data and enjoy seamless internet on-the-go.',
    image: '/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-03-300x300.png',
    alt: 'Connect with high speed data',
  },
];

let stepTimer;
let featuredPlanTimer;

onMounted(() => {
  stepTimer = window.setInterval(() => {
    activeStep.value = (activeStep.value + 1) % steps.length;
  }, 5000);

  featuredPlanTimer = window.setInterval(() => {
    advanceFeaturedPlan(1);
  }, 5000);
});

onBeforeUnmount(() => {
  window.clearInterval(stepTimer);
  window.clearInterval(featuredPlanTimer);
});

watch(
  () => props.featuredPlans.length,
  (count) => {
    if (activeFeaturedPlanIndex.value >= count) {
      activeFeaturedPlanIndex.value = 0;
    }
  },
);

const faqs = computed(() => {
  if (homepageContent.value.faqs?.length) {
    return homepageContent.value.faqs;
  }

  return [
    { question: 'How do I activate my eSIM?', answer: 'Scan the QR and install instantly from your BlipBlap account.' },
    { question: 'Which devices are supported?', answer: 'Most modern iPhone and Android devices with eSIM support will work.' },
    { question: 'When should I install my eSIM?', answer: 'Install it before your trip, then turn on data when you reach your destination.' },
    { question: 'How do loyalty rewards work?', answer: 'Earn points on eligible purchases and referrals, then redeem them for rewards.' },
  ];
});
</script>

<template>
  <section class="hero-section">
    <div class="hero-grid">
      <div class="hero-copy">
        <p class="eyebrow">{{ heroEyebrow }}</p>
        <h1>{{ heroTitle }}</h1>
        <p class="hero-text">{{ heroText }}</p>
        <a :href="heroCtaUrl" class="primary-cta">{{ heroCtaLabel }}</a>
      </div>

      <div class="hero-art" aria-hidden="true">
        <img
          class="hero-photo-img"
          :src="heroImageUrl"
          alt="Travelers using BlipBlap eSIM"
        />
      </div>
    </div>
  </section>

  <section v-if="promoBanners.length" class="plans-index-strip">
    <div v-for="(banner, index) in promoBanners" :key="`${banner.title}-${index}`">
      <strong>{{ banner.title }}</strong>
      <p>{{ banner.text }}</p>
      <a v-if="banner.cta_label && banner.cta_url" :href="banner.cta_url">{{ banner.cta_label }}</a>
    </div>
  </section>

  <section class="trust-panel">
    <span class="shape shape-lines"></span>
    <h2>{{ homepageContent.trust_heading || 'Why Travelers Worldwide Trust Blip Blap ESIM' }}</h2>
    <div class="trust-grid">
      <article v-for="item in contentTrustItems" :key="item.title">
        <img class="trust-icon-img" :src="item.image" :alt="item.title" />
        <h3>{{ item.title }}</h3>
        <p>{{ item.text }}</p>
      </article>
    </div>
  </section>

  <section v-if="featuredPlans.length" class="featured-plans-section">
    <div class="featured-plans-copy">
      <p class="eyebrow">Featured eSIM plans</p>
      <h2>Fast picks for your next trip</h2>
      <p>
        Admin-selected packages appear here with the key details customers need
        before checkout.
      </p>
    </div>

    <div
      class="featured-plans-rail"
      :class="{ 'is-dragging': featuredDragging }"
      :style="{ '--featured-drag-x': `${featuredDragDeltaX}px` }"
      @pointerdown="startFeaturedDrag"
      @pointermove="updateFeaturedDrag"
      @pointerup="finishFeaturedDrag"
      @pointercancel="finishFeaturedDrag"
      @pointerleave="finishFeaturedDrag"
    >
      <a
        v-for="(plan, index) in featuredPlans"
        :key="plan.id"
        class="featured-plan-tile"
        :class="featuredPlanState(index)"
        :draggable="false"
        :href="`/checkout/${plan.slug}`"
        @click="handleFeaturedPlanClick($event, plan)"
      >
        <span class="featured-plan-tile__location">{{ planLocation(plan) }}</span>
        <strong>{{ planDataLabel(plan) }}</strong>
        <small>{{ plan.duration_days }} {{ Number(plan.duration_days) === 1 ? 'day' : 'days' }}</small>
        <em>{{ plan.currency }} {{ Number(plan.retail_price).toFixed(2) }}</em>
        <span class="featured-plan-tile__name">{{ plan.title }}</span>
      </a>
    </div>
  </section>

  <section id="destinations" class="destinations-section">
    <h2>Your Gateway To Global<br />Connectivity</h2>
    <DestinationTabs v-model:active="activeTab" />
    <div class="destination-content">
      <h3>Get ESIMs For Popular Locations</h3>
      <div class="destination-grid">
        <a
          v-for="destination in visibleDestinations"
          :key="destination.name"
          class="destination-tile"
          :href="destinationUrl(destination.name)"
        >
          <span class="flag">
            <svg
              v-if="destination.icon === 'globe'"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="9" />
              <path d="M3 12h18M12 3c2.3 2.5 3.4 5.5 3.4 9S14.3 18.5 12 21M12 3c-2.3 2.5-3.4 5.5-3.4 9S9.7 18.5 12 21" />
            </svg>
            <img v-else-if="destination.flag_url" :src="destination.flag_url" :alt="`${destination.name} flag`" />
            <span v-else>{{ destination.iso }}</span>
          </span>
          <span>
            <strong>eSIM</strong>
            <small>{{ destination.name }}</small>
            <em v-if="destinationMeta(destination)">{{ destinationMeta(destination) }}</em>
          </span>
        </a>
      </div>
    </div>
  </section>

  <section class="steps-section">
    <div class="steps-illustration">
      <div class="step-visual-card">
        <Transition name="step-fade" mode="out-in">
          <img
            :key="steps[activeStep].image"
            class="active-step-image"
            :src="steps[activeStep].image"
            :alt="steps[activeStep].alt"
          />
        </Transition>
      </div>
    </div>
    <div class="steps-copy">
      <h2>Enjoy Unlimited Data In<br />3 Steps</h2>
      <ol>
        <li
          v-for="(step, index) in steps"
          :key="step.title"
          :class="{ active: activeStep === index }"
          tabindex="0"
        >
          <span>{{ index + 1 }}</span>
          <div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
          </div>
        </li>
      </ol>
    </div>
  </section>

  <section id="faqs" class="faq-section">
    <h2>{{ faqHeading }}</h2>
    <p>{{ faqIntro }}</p>
    <div class="faq-list">
      <details v-for="item in faqs" :key="item.question">
        <summary>{{ item.question }}</summary>
        <p>{{ item.answer }}</p>
      </details>
    </div>
  </section>

  <SiteFooter />

  <AuthRequiredModal
    v-if="authPromptCheckoutUrl"
    :checkout-url="authPromptCheckoutUrl"
    @close="authPromptCheckoutUrl = ''"
  />
</template>
