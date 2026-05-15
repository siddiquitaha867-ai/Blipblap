<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

defineProps({
  localPlans: {
    type: Array,
    default: () => [],
  },
  regionalPlans: {
    type: Array,
    default: () => [],
  },
  globalPlans: {
    type: Array,
    default: () => [],
  },
});

const priceLabel = (plan) => {
  if (!plan.min_price) {
    return 'View plans';
  }

  return `from ${plan.currency || 'USD'} ${Number(plan.min_price).toFixed(2)}`;
};
</script>

<template>
  <section class="plans-index-page">
    <section class="plans-index-hero">
      <div>
        <span>All BlipBlap eSIMs</span>
        <h1>Find the right eSIM plan by country, region, or worldwide coverage.</h1>
        <p>
          Browse every synced destination in one place, compare starting prices, then open the plan collection that matches your trip.
        </p>
      </div>
      <img src="/images/blipblap/Instant-information.gif" alt="BlipBlap eSIM setup preview">
    </section>

    <section class="plans-index-strip">
      <img src="/images/blipblap/Trip-1.gif" alt="">
      <div>
        <strong>Quick setup</strong>
        <p>Choose a destination, pay securely, then scan the QR code from your account or email.</p>
      </div>
      <img src="/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-02-300x300.png" alt="">
    </section>

    <section class="plans-index-section">
      <div class="plans-index-heading">
        <span>Country plans</span>
        <h2>Country plans</h2>
        <p>Best for one destination. Each card opens all available data packages for that country.</p>
      </div>
      <div class="plans-index-grid">
        <a v-for="plan in localPlans" :key="plan.name" :href="plan.url" class="plans-index-card">
          <span class="plans-index-icon">
            <img v-if="plan.flag_url" :src="plan.flag_url" :alt="`${plan.name} flag`">
            <span v-else>{{ plan.name.slice(0, 2).toUpperCase() }}</span>
          </span>
          <span>
            <strong>{{ plan.name }}</strong>
            <small>{{ priceLabel(plan) }} · {{ plan.plan_count }} plans</small>
          </span>
        </a>
      </div>
    </section>

    <section class="plans-index-section plans-index-section--soft">
      <div class="plans-index-heading">
        <span>Regional packs</span>
        <h2>Multi-country coverage</h2>
        <p>For trips crossing borders, regional packs keep you connected across a wider area.</p>
      </div>
      <div class="plans-index-grid">
        <a v-for="plan in regionalPlans" :key="plan.name" :href="plan.url" class="plans-index-card">
          <span class="plans-index-icon">
            <img v-if="plan.flag_url" :src="plan.flag_url" :alt="`${plan.name} flag`">
            <span v-else>{{ plan.name.slice(0, 2).toUpperCase() }}</span>
          </span>
          <span>
            <strong>{{ plan.name }}</strong>
            <small>{{ priceLabel(plan) }} · {{ plan.plan_count }} plans</small>
          </span>
        </a>
      </div>
    </section>

    <section class="plans-index-section">
      <div class="plans-index-heading">
        <span>Global eSIMs</span>
        <h2>Worldwide plans</h2>
        <p>Pick a global bundle when you need broad coverage and want one simple plan.</p>
      </div>
      <div class="plans-index-grid">
        <a v-for="plan in globalPlans" :key="plan.name" :href="plan.url" class="plans-index-card">
          <span class="plans-index-icon plans-index-icon--globe">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="9" />
              <path d="M3 12h18M12 3c2.3 2.5 3.4 5.5 3.4 9S14.3 18.5 12 21M12 3c-2.3 2.5-3.4 5.5-3.4 9S9.7 18.5 12 21" />
            </svg>
          </span>
          <span>
            <strong>{{ plan.name }}</strong>
            <small>{{ priceLabel(plan) }} · {{ plan.plan_count }} plans</small>
          </span>
        </a>
      </div>
    </section>

    <section class="plans-index-final">
      <h2>Ready to connect?</h2>
      <p>Open a destination, choose your data package, and BlipBlap will guide you through checkout and installation.</p>
      <a href="/esim-plans">Browse all eSIM plans</a>
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
      </div>
      <div>
        <h3>Legal</h3>
        <a href="#">Terms And Conditions</a>
        <a href="#">Privacy Policy</a>
        <a href="/how-blipblap-works">How BlipBlap Works</a>
      </div>
    </footer>
  </section>
</template>
