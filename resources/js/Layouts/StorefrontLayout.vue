<script setup>
import SupportChatWidget from '@/Components/SupportChatWidget.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const searchOpen = ref(false);
const searchLoading = ref(false);
const searchQuery = ref('');
const destinations = ref([]);
const accountOpen = ref(false);
const mobileMenuOpen = ref(false);

const user = computed(() => page.props.auth.user);
const isAdminPreview = computed(() => Boolean(user.value?.is_admin));
const homeHref = computed(() => (isAdminPreview.value ? '/admin/storefront' : '/'));
const displayName = computed(() => (isAdminPreview.value ? 'Admin' : user.value?.name));
const hasCustomerEsims = computed(() => Number(user.value?.customer_esims_count || 0) > 0);

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

const closeSearchWhenLeaving = (event) => {
  if (event.currentTarget.contains(event.relatedTarget)) {
    return;
  }

  searchOpen.value = false;
};

const closeAccountWhenLeaving = (event) => {
  if (event.currentTarget.contains(event.relatedTarget)) {
    return;
  }

  accountOpen.value = false;
};

const syncDesktopState = () => {
  if (window.innerWidth > 900) {
    mobileMenuOpen.value = false;
  }
};

const setMobileMenuOpen = (open) => {
  mobileMenuOpen.value = open;
};

const lockPageScroll = (locked) => {
  if (typeof document === 'undefined') {
    return;
  }

  document.body.style.overflow = locked ? 'hidden' : '';
  document.documentElement.style.overflow = locked ? 'hidden' : '';
};

onMounted(() => {
  syncDesktopState();
  window.addEventListener('resize', syncDesktopState);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncDesktopState);
  lockPageScroll(false);
});

watch(mobileMenuOpen, (open) => {
  lockPageScroll(open);

  if (!open) {
    accountOpen.value = false;
    searchOpen.value = false;
  }
});
</script>

<template>
  <div class="site-shell">
    <header class="air-header">
      <div class="air-header-inner">
        <div v-if="isAdminPreview" class="preview-bar">
          Admin storefront preview. Purchases are disabled.
        </div>
        <div class="air-header-top">
          <Link :href="homeHref" class="brand" aria-label="BlipBlap home">
            <img src="/images/blipblap/logo-blue.png" alt="BlipBlap" />
          </Link>

          <button
            type="button"
            class="mobile-menu-toggle"
            :aria-expanded="mobileMenuOpen"
            aria-controls="site-navigation-panel"
            @click="setMobileMenuOpen(!mobileMenuOpen)"
          >
            <span></span>
            <span></span>
            <span></span>
            <span class="sr-only">Toggle navigation</span>
          </button>

          <div class="air-header-actions air-header-actions--desktop">
            <template v-if="user">
              <Link v-if="hasCustomerEsims" href="/my-esims" class="account-link" @click="setMobileMenuOpen(false)">My eSIMs</Link>
              <button v-else type="button" class="account-link account-link--disabled" aria-disabled="true">
                My eSIMs
              </button>

              <div class="account-menu-wrap" @focusout="closeAccountWhenLeaving">
                <button
                  type="button"
                  class="pill account-trigger"
                  :aria-expanded="accountOpen"
                  @click="accountOpen = !accountOpen"
                >
                  My Account
                </button>

                <div v-show="accountOpen" class="account-menu">
                  <span>Signed in as</span>
                  <strong>{{ displayName }}</strong>
                  <small>{{ user.email }}</small>
                  <Link href="/logout" method="post" as="button" class="account-logout">Logout</Link>
                </div>
              </div>
            </template>
            <template v-else>
              <Link href="/auth/login" class="pill pill-soft" @click="setMobileMenuOpen(false)">Log in</Link>
              <Link href="/auth/signup" class="pill" @click="setMobileMenuOpen(false)">Sign up</Link>
            </template>
          </div>
        </div>

        <div
          id="site-navigation-panel"
          class="air-header-body"
          :class="{ 'is-open': mobileMenuOpen }"
        >

          <nav class="air-nav" aria-label="Primary navigation">
            <Link :href="homeHref" @click="setMobileMenuOpen(false)">Home</Link>
            <Link href="/esim-plans" @click="setMobileMenuOpen(false)">ESIM Plans</Link>
            <Link href="/how-blipblap-works" @click="setMobileMenuOpen(false)">How BlipBlap Works</Link>
            <a :href="`${homeHref}#faqs`" @click="setMobileMenuOpen(false)">FAQs</a>
            <a :href="`${homeHref}#contact`" @click="setMobileMenuOpen(false)">Contact Us</a>
          </nav>

          <div class="air-header-actions air-header-actions--mobile">
            <template v-if="user">
              <Link v-if="hasCustomerEsims" href="/my-esims" class="account-link" @click="setMobileMenuOpen(false)">My eSIMs</Link>
              <button v-else type="button" class="account-link account-link--disabled" aria-disabled="true">
                My eSIMs
              </button>

              <div class="account-menu-wrap" @focusout="closeAccountWhenLeaving">
                <button
                  type="button"
                  class="pill account-trigger"
                  :aria-expanded="accountOpen"
                  @click="accountOpen = !accountOpen"
                >
                  My Account
                </button>

                <div v-show="accountOpen" class="account-menu">
                  <span>Signed in as</span>
                  <strong>{{ displayName }}</strong>
                  <small>{{ user.email }}</small>
                  <Link href="/logout" method="post" as="button" class="account-logout">Logout</Link>
                </div>
              </div>
            </template>
            <template v-else>
              <Link href="/auth/login" class="pill pill-soft" @click="setMobileMenuOpen(false)">Log in</Link>
              <Link href="/auth/signup" class="pill" @click="setMobileMenuOpen(false)">Sign up</Link>
            </template>
          </div>

          <div class="air-search-row">
            <span class="air-line"></span>
            <div class="air-search-wrap" @focusout="closeSearchWhenLeaving">
              <form class="air-search" @submit.prevent="loadDestinations">
                <span>Search</span>
                <input
                  v-model="searchQuery"
                  type="search"
                  placeholder="Where do you need an eSIM?"
                  autocomplete="off"
                  @focus="loadDestinations"
                  @input="loadDestinations"
                />
                <button type="button" @click="loadDestinations">Locations</button>
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
                      @click="setMobileMenuOpen(false)"
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
        </div>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <SupportChatWidget :hidden="isAdminPreview || mobileMenuOpen" />
  </div>
</template>
