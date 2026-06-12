<template>
  <footer id="contact" class="footer" :class="{ 'plan-footer': plan }">
    <div class="footer-brand">
      <a href="/" class="footer-logo" aria-label="BlipBlap home">
        <img src="/images/blipblap/logo-inc.png" alt="Blip Blap Inc." />
      </a>
      <p>Instant eSIM plans for local, regional, and worldwide travel.</p>
      <small>Copyright 2026, Blip Blap Inc.</small>
    </div>

    <nav class="footer-group" aria-label="Explore BlipBlap">
      <h3>Explore</h3>
      <a href="/esim-plans">All eSIM plans</a>
      <a href="/destinations/united-arab-emirates">United Arab Emirates eSIM</a>
      <a href="/destinations/europe">Europe eSIM</a>
      <a href="/destinations/saudi-arabia">Saudi Arabia eSIM</a>
      <a href="/destinations/united-kingdom">United Kingdom eSIM</a>
    </nav>

    <nav class="footer-group" aria-label="Help and legal">
      <h3>Support</h3>
      <a href="/how-blipblap-works">How BlipBlap Works</a>
      <a href="/#faqs">FAQs</a>
      <a href="/#contact">Contact Us</a>
      <a href="/terms-and-conditions">Terms and Conditions</a>
      <a href="/privacy-policy">Privacy Policy</a>
    </nav>
  </footer>
</template>

<script setup>
defineProps({
  plan: {
    type: Boolean,
    default: false,
  },
});
</script>
