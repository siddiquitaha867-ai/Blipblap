<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  checkoutUrl: {
    type: String,
    required: true,
  },
});

defineEmits(['close']);

const authUrl = (path, checkoutUrl) => `${path}?redirect=${encodeURIComponent(checkoutUrl)}`;
</script>

<template>
  <Teleport to="body">
    <div
      class="auth-required-backdrop"
      role="dialog"
      aria-modal="true"
      aria-labelledby="auth-required-title"
      @click.self="$emit('close')"
    >
      <section class="auth-required-dialog">
        <button
          type="button"
          class="auth-required-close"
          aria-label="Close"
          @click="$emit('close')"
        >
          x
        </button>
        <p class="eyebrow">Account required</p>
        <h2 id="auth-required-title">Log in or create your BlipBlap account</h2>
        <p>
          Your eSIM QR code and install details are saved inside your account after payment.
        </p>
        <div class="auth-required-actions">
          <Link :href="authUrl('/auth/login', checkoutUrl)" class="auth-required-primary">Log in</Link>
          <Link :href="authUrl('/auth/signup', checkoutUrl)" class="auth-required-secondary">Create account</Link>
        </div>
      </section>
    </div>
  </Teleport>
</template>
