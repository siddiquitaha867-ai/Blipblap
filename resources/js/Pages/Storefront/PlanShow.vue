<script setup>
import { computed, ref, watchEffect } from 'vue';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import { usePage } from '@inertiajs/vue3';

defineOptions({ layout: StorefrontLayout });

const props = defineProps({
  plan: {
    type: Object,
    required: true,
  },
  relatedPlans: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const isAdminPreview = computed(() => Boolean(page.props.auth.user?.is_admin));
const activeDuration = ref(Number(props.plan.duration_days || 0));
const compatibilityOpen = ref(false);
const activeDeviceCategory = ref('ios');
const deviceSearch = ref('');

const esimDevices = [
  {
    key: 'ios',
    label: 'iOS / iPadOS',
    devices: [
      'iPhone 17, 17 Pro, 17 Pro Max',
      'iPhone Air',
      'iPhone 16, 16e, 16 Plus, 16 Pro, 16 Pro Max',
      'iPhone 15, 15 Plus, 15 Pro, 15 Pro Max',
      'iPhone 14, 14 Plus, 14 Pro, 14 Pro Max',
      'iPhone 13, 13 mini, 13 Pro, 13 Pro Max',
      'iPhone 12, 12 mini, 12 Pro, 12 Pro Max',
      'iPhone 11, 11 Pro, 11 Pro Max',
      'iPhone XS, XS Max, XR',
      'iPhone SE 2nd gen / 3rd gen',
      'iPad 7th gen or later, Wi-Fi + Cellular',
      'iPad Air 3rd gen or later, Wi-Fi + Cellular',
      'iPad Pro 11-inch 1st gen or later, Wi-Fi + Cellular',
      'iPad Pro 12.9-inch 3rd gen or later, Wi-Fi + Cellular',
      'iPad mini 5th gen or later, Wi-Fi + Cellular',
    ],
  },
  {
    key: 'android',
    label: 'Android',
    devices: [
      'Samsung Galaxy S20, S20+, S20 Ultra',
      'Samsung Galaxy S21, S21+, S21 Ultra',
      'Samsung Galaxy S22, S22+, S22 Ultra',
      'Samsung Galaxy S23, S23+, S23 Ultra, S23 FE',
      'Samsung Galaxy S24, S24+, S24 Ultra, S24 FE',
      'Samsung Galaxy S25, S25+, S25 Ultra, S25 Slim',
      'Samsung Galaxy Note20, Note20 Ultra',
      'Samsung Galaxy Z Flip, Flip 3, Flip 4, Flip 5, Flip 6',
      'Samsung Galaxy Z Fold 2, Fold 3, Fold 4, Fold 5, Fold 6',
      'Google Pixel 2, 2 XL',
      'Google Pixel 3, 3 XL, 3a, 3a XL',
      'Google Pixel 4, 4 XL, 4a, 4a 5G',
      'Google Pixel 5, 5a',
      'Google Pixel 6, 6a, 6 Pro',
      'Google Pixel 7, 7a, 7 Pro',
      'Google Pixel 8, 8a, 8 Pro',
      'Google Pixel 9, 9 Pro, 9 Pro XL, 9 Pro Fold',
      'Google Pixel Fold',
      'Motorola Razr 2019, Razr 5G, Razr 40, Razr 40 Ultra',
      'Motorola Edge 40 Pro, Edge 50 Pro, Edge 50 Ultra',
      'Huawei P40, P40 Pro, Mate 40 Pro',
      'Oppo Find X3 Pro, Find X5, Find X5 Pro, Find X8 Pro',
      'Oppo Reno 5A, Reno 6 Pro 5G, Reno 9A',
      'Xiaomi 12T Pro, 13, 13 Lite, 13 Pro, 14, 14 Pro, 14T Pro',
      'Sony Xperia 10 III Lite, 10 IV, 10 V, 10 VI',
      'Sony Xperia 1 IV, 1 V, 1 VI, 5 IV, 5 V',
      'OnePlus 11, 12, Open',
      'Nothing Phone (2), Phone (2a), Phone (2a) Plus',
      'Fairphone 4, Fairphone 5',
    ],
  },
  {
    key: 'other',
    label: 'Other devices',
    devices: [
      'Microsoft Surface Pro LTE Advanced',
      'Microsoft Surface Pro X',
      'Microsoft Surface Pro 5G',
      'Microsoft Surface Go 2 LTE',
      'Microsoft Surface Go 3 LTE',
      'Microsoft Surface Duo, Duo 2',
      'Lenovo ThinkPad X1 Fold',
      'Lenovo ThinkPad X1 Nano',
      'Lenovo ThinkPad X1 Titanium Yoga',
      'Lenovo Yoga 5G',
      'HP Elite Dragonfly G2',
      'HP Spectre Folio',
      'HP ProBook G5',
      'Gemini PDA',
      'Rakuten Mini, Big, Big-S, Hand',
      'Nuu Mobile X5',
      'Planet Astro Slide',
      'Windows laptops/tablets with LTE or 5G eSIM support',
    ],
  },
];

const planDataLabel = (item) => {
  return item.unlimited ? 'Unlimited' : `${Number(item.data_amount)} ${item.data_unit}`;
};

const groupedPlans = computed(() => {
  const groups = new Map();

  props.relatedPlans.forEach((item) => {
    const days = Number(item.duration_days || 0);

    if (!groups.has(days)) {
      groups.set(days, []);
    }

    groups.get(days).push(item);
  });

  return Array.from(groups.entries())
    .sort(([a], [b]) => a - b)
    .map(([days, plans]) => ({
      days,
      label: `${days} ${days === 1 ? 'day' : 'days'}`,
      plans: plans.slice().sort((a, b) => {
        if (Boolean(a.unlimited) !== Boolean(b.unlimited)) {
          return a.unlimited ? 1 : -1;
        }

        return Number(a.data_amount || 0) - Number(b.data_amount || 0);
      }),
    }));
});

const selectedGroup = computed(() => {
  return groupedPlans.value.find((group) => group.days === activeDuration.value)
    || groupedPlans.value[0]
    || { days: 0, label: '', plans: [] };
});

const activeDeviceGroup = computed(() => {
  return esimDevices.find((group) => group.key === activeDeviceCategory.value) || esimDevices[0];
});

const filteredDevices = computed(() => {
  const query = deviceSearch.value.trim().toLowerCase();

  if (!query) {
    return activeDeviceGroup.value.devices;
  }

  return activeDeviceGroup.value.devices.filter((device) => device.toLowerCase().includes(query));
});

const openCompatibility = () => {
  compatibilityOpen.value = true;
};

const closeCompatibility = () => {
  compatibilityOpen.value = false;
};

watchEffect(() => {
  if (!groupedPlans.value.length) {
    return;
  }

  const hasActiveDuration = groupedPlans.value.some((group) => group.days === activeDuration.value);

  if (!hasActiveDuration) {
    activeDuration.value = groupedPlans.value[0].days;
  }
});
</script>

<template>
  <section class="airalo-flow-page">
    <div class="detail-hero plan-hero">
      <div>
        <p class="eyebrow">Local eSIM</p>
        <h1>{{ plan.country_name || plan.title }} eSIM</h1>
        <p>
          Pick a prepaid data package, checkout securely, then scan your QR code
          to connect as soon as you land.
        </p>
      </div>

      <aside class="network-panel">
        <span>Network</span>
        <strong>{{ plan.country_name || 'Best available network' }}</strong>
        <button type="button" class="compatibility-trigger" @click="openCompatibility">
          Check compatibility
        </button>
        <dl>
          <div>
            <dt>Plan type</dt>
            <dd>Data only</dd>
          </div>
          <div>
            <dt>Top-up</dt>
            <dd>{{ plan.topup_supported ? 'Available' : 'Available where supported' }}</dd>
          </div>
          <div>
            <dt>Activation</dt>
            <dd>Starts when your eSIM connects to a supported network.</dd>
          </div>
        </dl>
      </aside>
    </div>

    <div class="package-picker">
      <div class="package-picker__header">
        <div>
          <span class="package-picker__eyebrow">Available plans</span>
          <h2>Choose your package</h2>
        </div>
        <p>{{ selectedGroup.plans.length }} packages for {{ selectedGroup.label }}</p>
      </div>

      <div class="duration-tabs" role="tablist" aria-label="Package duration">
        <button
          v-for="group in groupedPlans"
          :key="group.days"
          type="button"
          class="duration-tab"
          :class="{ active: activeDuration === group.days }"
          role="tab"
          :aria-selected="activeDuration === group.days"
          @click="activeDuration = group.days"
        >
          <strong>{{ group.label }}</strong>
          <span>{{ group.plans.length }} plans</span>
        </button>
      </div>

      <p v-if="isAdminPreview" class="preview-note">
        Admin preview mode is active. Plans are visible, but checkout and purchases are disabled.
      </p>

      <div class="package-grid" role="tabpanel">
        <template v-if="!isAdminPreview">
          <a
            v-for="item in selectedGroup.plans"
            :key="item.id"
            :href="`/checkout/${item.slug}`"
            class="package-card"
          >
            <span>{{ item.duration_days }} days</span>
            <strong>{{ planDataLabel(item) }}</strong>
            <em>{{ item.currency }} {{ Number(item.retail_price).toFixed(2) }}</em>
          </a>
        </template>
        <template v-else>
          <button
            v-for="item in selectedGroup.plans"
            :key="item.id"
            type="button"
            class="package-card package-card--disabled"
            disabled
          >
            <span>{{ item.duration_days }} days</span>
            <strong>{{ planDataLabel(item) }}</strong>
            <em>{{ item.currency }} {{ Number(item.retail_price).toFixed(2) }}</em>
          </button>
        </template>
      </div>
    </div>

    <section id="compatibility" class="support-band">
      <h2>Before you buy</h2>
      <div>
        <article>
          <strong>Check device compatibility</strong>
          <p>Most recent iPhone and Android devices support eSIM. Keep your physical SIM active alongside BlipBlap.</p>
        </article>
        <article>
          <strong>Install in minutes</strong>
          <p>After payment, your account shows QR, SM-DP+ address, and matching ID/manual code.</p>
        </article>
        <article>
          <strong>Need wider coverage?</strong>
          <p>Regional and worldwide eSIM packages follow the same package selection workflow.</p>
        </article>
      </div>
    </section>

    <section class="comment-section">
      <div class="comment-copy">
        <span>Traveler feedback</span>
        <h2>Leave your comment</h2>
        <p>
          Share a quick note about this eSIM plan, coverage questions, or anything
          you want the BlipBlap team to review.
        </p>
      </div>
      <form class="comment-form">
        <label>
          <span>Name</span>
          <input type="text" placeholder="Your name">
        </label>
        <label>
          <span>Email</span>
          <input type="email" placeholder="you@example.com">
        </label>
        <label class="comment-field">
          <span>Comment</span>
          <textarea rows="5" placeholder="Write your comment"></textarea>
        </label>
        <button type="button">Submit comment</button>
      </form>
    </section>

    <section class="app-cta plan-app-cta">
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

    <footer id="contact" class="footer plan-footer">
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

    <Teleport to="body">
      <div
        v-if="compatibilityOpen"
        class="compatibility-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="compatibility-title"
        @click.self="closeCompatibility"
      >
        <div class="compatibility-dialog">
          <button
            type="button"
            class="compatibility-close"
            aria-label="Close compatibility popup"
            @click="closeCompatibility"
          >
            ×
          </button>

          <div class="compatibility-head">
            <span>Device check</span>
            <h2 id="compatibility-title">eSIM compatible devices</h2>
            <p>Search your model, or dial *#06# and check for an EID number.</p>
          </div>

          <label class="compatibility-search">
            <span>Search device</span>
            <input
              v-model="deviceSearch"
              type="search"
              placeholder="iPhone 15, Pixel 8, Galaxy S24..."
              autocomplete="off"
            >
          </label>

          <div class="compatibility-tabs" role="tablist" aria-label="Device type">
            <button
              v-for="group in esimDevices"
              :key="group.key"
              type="button"
              :class="{ active: activeDeviceCategory === group.key }"
              role="tab"
              :aria-selected="activeDeviceCategory === group.key"
              @click="activeDeviceCategory = group.key"
            >
              {{ group.label }}
            </button>
          </div>

          <div class="compatibility-list" role="tabpanel">
            <div
              v-for="device in filteredDevices"
              :key="device"
              class="compatibility-device"
            >
              {{ device }}
            </div>
            <p v-if="!filteredDevices.length" class="compatibility-empty">
              No matching device found. Please check your exact model with the manufacturer.
            </p>
          </div>

          <p class="compatibility-note">
            Note: some carrier-locked, region-specific, China mainland, Hong Kong, or Macao variants may not support travel eSIM activation.
          </p>
        </div>
      </div>
    </Teleport>
  </section>
</template>
