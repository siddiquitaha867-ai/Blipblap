<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const plansOpen = ref(false);
</script>

<template>
  <div class="site-shell">
    <header class="air-header">
      <div class="air-header-top">
        <Link href="/" class="brand" aria-label="BlipBlap home">
          <img src="/images/blipblap/logo-blue.png" alt="BlipBlap" />
        </Link>

        <div class="air-header-actions">
          <button type="button" class="icon-button" aria-label="Language">◎</button>
          <span class="header-divider"></span>
          <button type="button" class="icon-button" aria-label="Wallet">▣</button>
          <template v-if="page.props.auth.user">
            <span class="nav-user">{{ page.props.auth.user.name }}</span>
            <Link href="/logout" method="post" as="button" class="pill">Logout</Link>
          </template>
          <template v-else>
            <Link href="/auth/login" class="pill pill-soft">Log in</Link>
            <Link href="/auth/signup" class="pill">Sign up</Link>
          </template>
        </div>
      </div>

      <nav class="air-nav" aria-label="Primary navigation">
        <Link href="/">Home</Link>
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
        <a href="/#loyalty">Loyalty Program</a>
        <a href="/#faqs">FAQs</a>
        <a href="/#contact">Contact Us</a>
      </nav>

      <div class="air-search-row">
        <span class="air-line"></span>
        <form class="air-search" action="/destinations/pakistan">
          <span>⌕</span>
          <input type="search" placeholder="Where do you need an eSIM?" />
          <button type="submit">Locations⌄</button>
        </form>
        <span class="air-line"></span>
      </div>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>
