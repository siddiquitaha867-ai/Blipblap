<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  mode: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <section class="auth-modal" aria-label="Authentication">
    <div class="auth-modal-top">
      <Link href="/" class="auth-close" aria-label="Close">×</Link>
    </div>

    <div class="auth-tabs">
      <Link href="/auth/login" :class="{ active: mode === 'login' }">Log in</Link>
      <Link href="/auth/signup" :class="{ active: mode === 'signup' }">Sign up</Link>
    </div>

    <div class="social-row" aria-label="Social sign in options">
      <button type="button" title="Apple sign in will be connected with OAuth credentials">●</button>
      <button type="button" title="Google sign in will be connected with OAuth credentials">G</button>
      <button type="button" title="Facebook sign in will be connected with OAuth credentials">f</button>
    </div>

    <slot />
  </section>
</template>
