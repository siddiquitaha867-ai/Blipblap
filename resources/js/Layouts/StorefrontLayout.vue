<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const plansOpen = ref(false);
const searchOpen = ref(false);
const searchLoading = ref(false);
const searchQuery = ref('');
const destinations = ref([]);

const isAdminPreview = computed(() => Boolean(page.props.auth.user?.is_admin));
const homeHref = computed(() => (isAdminPreview.value ? '/admin/storefront' : '/'));
const displayName = computed(() => (isAdminPreview.value ? 'Admin' : page.props.auth.user?.name));

const loadDestinations = async () => {
  searchOpen.value = true;

  if (destinations.value.length || searchLoading.value) {
    return;
  }

  searchLoading.value = true;

  try {
    const response = await fetch('/destinations-list', { headers: { Accept: 'application/json' } });
    destinations.value = await response.json();
  } catch {
    destinations.value = [];
  } finally {
    searchLoading.value = false;
  }
};

const filteredDestinations = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return destinations.value
    .filter((destination) => !query || destination.name.toLowerCase().includes(query));
});

const destinationMeta = (destination) => {
  const parts = [];

  if (destination.min_price) {
    parts.push(`from ${destination.currency || 'USD'} ${Number(destination.min_price).toFixed(2)}`);
  }

  if (destination.plan_count) {
    parts.push(`${destination.plan_count} plans`);
  }

  return parts.join(' - ');
};
</script>

<template>
  <div class="site-shell">
    <header class="air-header">
      <div v-if="isAdminPreview" class="preview-bar">
        Admin storefront preview. Purchases are disabled.
      </div>
      <div class="air-header-top">
        <Link :href="homeHref" class="brand" aria-label="BlipBlap home">
          <img src="/images/blipblap/logo-blue.png" alt="BlipBlap" />
        </Link>

        <div class="air-header-actions">
          <button type="button" class="icon-button" aria-label="Language">○</button>
          <span class="header-divider"></span>
          <button type="button" class="icon-button" aria-label="Wallet">□</button>
          <template v-if="page.props.auth.user">
            <span class="nav-user">{{ displayName }}</span>
            <Link href="/logout" method="post" as="button" class="pill">Logout</Link>
          </template>
          <template v-else>
            <Link href="/auth/login" class="pill pill-soft">Log in</Link>
            <Link href="/auth/signup" class="pill">Sign up</Link>
          </template>
        </div>
      </div>

      <nav class="air-nav" aria-label="Primary navigation">
        <Link :href="homeHref">Home</Link>
        <div
          class="nav-dropdown"
          @mouseenter="plansOpen = true"
          @mouseleave="plansOpen = false"
        >
          <button type="button" @click="plansOpen = !plansOpen">
            ESIM Plans <span>⌄</span>
          </button>
          <div v-show="plansOpen" class="plans-menu">
            <Link href="/destinations/canada">Canada eSIM</Link>
            <Link href="/destinations/usa">USA + Canada eSIM</Link>
            <Link href="/destinations/global">Global eSIM</Link>
            <Link href="/destinations/asia">Regional eSIM</Link>
          </div>
        </div>
        <Link href="/how-blipblap-works">How BlipBlap Works</Link>
        <a :href="`${homeHref}#faqs`">FAQs</a>
        <a :href="`${homeHref}#contact`">Contact Us</a>
      </nav>

      <div class="air-search-row">
        <span class="air-line"></span>
        <div class="air-search-wrap" @focusout="searchOpen = false">
          <form class="air-search" @submit.prevent="loadDestinations">
            <span>⌕</span>
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Where do you need an eSIM?"
              autocomplete="off"
              @focus="loadDestinations"
              @input="loadDestinations"
            />
            <button type="button" @click="loadDestinations">Locations⌄</button>
          </form>

          <div v-show="searchOpen" class="destination-search-menu">
            <label>
              <span>Search country</span>
              <input
                v-model="searchQuery"
                type="search"
                placeholder="Type country name..."
                autocomplete="off"
                @focus="searchOpen = true"
              />
            </label>

            <div class="destination-search-list">
              <p v-if="searchLoading" class="destination-search-state">Loading destinations...</p>
              <p v-else-if="!filteredDestinations.length" class="destination-search-state">No countries found.</p>
              <template v-else>
                <Link
                  v-for="destination in filteredDestinations"
                :key="destination.name"
                :href="destination.url"
                class="destination-search-item"
                @mousedown.prevent
              >
                  <span class="destination-search-flag">
                    <img v-if="destination.flag_url" :src="destination.flag_url" :alt="`${destination.name} flag`">
                    <span v-else>{{ destination.iso }}</span>
                  </span>
                  <span>
                    <strong>{{ destination.name }}</strong>
                    <small>{{ destinationMeta(destination) }}</small>
                  </span>
                </Link>
              </template>
            </div>
          </div>
        </div>
        <span class="air-line"></span>
      </div>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>
