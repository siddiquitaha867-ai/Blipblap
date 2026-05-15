<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import DestinationTabs from '@/Components/DestinationTabs.vue';
import AuthRequiredModal from '@/Components/AuthRequiredModal.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
});

const trustItems = [
  ['GLOBAL COVERAGE', 'Stay connected across Canada, USA & 190 worldwide destinations.'],
  ['INSTANT ACTIVATION', 'No physical SIM, just scan the QR and start.'],
  ['AFFORDABLE & TRANSPARENT', 'No hidden fees, no roaming surprises.'],
  ['24/7 SUPPORT', 'We are here wherever you need help.'],
];

const activeTab = ref('Top Destinations');
const activeStep = ref(0);
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

const visibleDestinations = computed(() => (groups.value[activeTab.value] || []).slice(0, 9));

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

const faqs = [
  'How do I activate my eSIM?',
  'Which devices are supported?',
  'Can I keep my physical SIM?',
  'How do loyalty rewards work?',
];
</script>

<template>
  <section class="hero-section">
    <div class="hero-grid">
      <div class="hero-copy">
        <p class="eyebrow">Complete eSIM Connectivity Platform</p>
        <h1>Blip Blap Fast, Reliable ESIM</h1>
        <p class="hero-text">
          Instant Canada & Global eSIM plans, activate in seconds with zero hassle.
          Connect across 190+ countries with affordable data and 24/7 support.
        </p>
        <a href="#destinations" class="primary-cta">Explore eSIM Plans</a>
      </div>

      <div class="hero-art" aria-hidden="true">
        <img
          class="hero-photo-img"
          src="/images/blipblap/186b5d5e-23fd-41a6-a460-8eba5e2f9410-300x300.webp"
          alt="Travelers using BlipBlap eSIM"
        />
      </div>
    </div>
  </section>

  <section class="trust-panel">
    <span class="shape shape-lines"></span>
    <h2>Why Travelers Worldwide<br />Trust Blip Blap ESIM</h2>
    <div class="trust-grid">
      <article v-for="item in trustItems" :key="item[0]">
        <img class="trust-icon-img" src="/images/blipblap/trust-icon.webp" alt="" />
        <h3>{{ item[0] }}</h3>
        <p>{{ item[1] }}</p>
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

    <div class="featured-plans-rail">
      <a
        v-for="plan in featuredPlans"
        :key="plan.id"
        class="featured-plan-tile"
        :href="`/checkout/${plan.slug}`"
        @click="requestCheckout($event, plan)"
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
            <img v-if="destination.flag_url" :src="destination.flag_url" :alt="`${destination.name} flag`" />
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
      <img class="steps-person" src="/images/blipblap/Mask-Group-15-300x300.png" alt="" />
      <div class="step-phone-frame">
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
          @focus="activeStep = index"
          @mouseenter="activeStep = index"
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
    <h2>Our FAQs Are A Great Place<br />To Find Answers Quickly.</h2>
    <p>A compilation of questions and answers that will help you decide.</p>
    <div class="faq-list">
      <details v-for="question in faqs" :key="question">
        <summary>{{ question }}</summary>
        <p v-if="question.includes('activate')">Scan the QR and install instantly from your BlipBlap account.</p>
        <p v-else-if="question.includes('devices')">Most modern iPhone and Android devices with eSIM support will work.</p>
        <p v-else-if="question.includes('physical')">Yes, you can use your eSIM alongside your original physical SIM.</p>
        <p v-else>Earn points on eligible purchases and referrals, then redeem them for rewards.</p>
      </details>
    </div>
  </section>

  <section class="app-cta">
    <div>
      <h2>Download the App<br />and manage your<br />eSIMs easily.</h2>
      <p>The quick & easy way to manage your eSIMs</p>
      <div class="store-buttons">
        <a href="#"><img src="/images/blipblap/pngwing.com_-e1764851044946-300x99.png" alt="Get it on Google Play" /></a>
        <a href="#"><img src="/images/blipblap/pngwing.com-1-300x116.png" alt="Download on the App Store" /></a>
      </div>
    </div>
    <img class="group-photo" src="/images/blipblap/Buddies.gif" alt="" />
  </section>

  <footer id="contact" class="footer">
    <div>
      <h2><span class="brand-mark">b</span> Blip Blap</h2>
      <p>Copyright 2026, Blip Blap</p>
    </div>
    <div>
      <h3>Site Map</h3>
      <a href="/destinations/united-arab-emirates">eSIM United Arab Emirates</a>
      <a href="/destinations/europe">eSIM Europe</a>
      <a href="/destinations/saudi-arabia">eSIM Saudi Arabia</a>
      <a href="/destinations/russia">eSIM Russia</a>
      <a href="/destinations/united-kingdom">eSIM United Kingdom</a>
    </div>
    <div>
      <h3>Legal</h3>
      <a href="#">Terms And Conditions</a>
      <a href="#">Privacy Policy</a>
      <a href="#contact">Contact Us</a>
    </div>
  </footer>

  <AuthRequiredModal
    v-if="authPromptCheckoutUrl"
    :checkout-url="authPromptCheckoutUrl"
    @close="authPromptCheckoutUrl = ''"
  />
</template>
