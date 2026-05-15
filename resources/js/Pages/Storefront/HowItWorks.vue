<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

defineOptions({ layout: StorefrontLayout });

const highlights = [
  ['No physical SIM', 'Install a digital data plan on an eSIM-ready phone.'],
  ['Instant delivery', 'After checkout, your install details appear in your account.'],
  ['Travel ready', 'Scan the QR code, enable the line, and connect when you arrive.'],
];

const buyingSteps = [
  ['Choose destination', 'Open all plans, select your country or regional pack, then pick the data and validity that fits your trip.'],
  ['Create account', 'Sign in or register so your eSIM, QR code, and order history stay safely attached to you.'],
  ['Pay securely', 'Complete checkout. Once payment is confirmed, BlipBlap requests the eSIM from the supplier.'],
  ['Install and connect', 'Open the ready page or email, scan the QR code, then turn on the eSIM line from your phone settings.'],
];

const phoneFrames = [
  ['Plan', 'Pakistan 5GB / 30 days'],
  ['Checkout', 'Secure payment'],
  ['QR ready', 'Scan to install'],
];
</script>

<template>
  <section class="how-page">
    <section class="how-hero">
      <div class="how-hero__copy">
        <span>How BlipBlap Works</span>
        <h1>BlipBlap eSIM keeps your phone online before the trip even starts.</h1>
        <p>
          Buy a travel data plan, receive digital eSIM installation details, and connect on your mobile without waiting for a physical SIM card.
        </p>
        <a href="/#destinations">Show all country plans</a>
      </div>
      <div class="how-hero__visual" aria-hidden="true">
        <img src="/images/blipblap/Trip.gif" alt="">
      </div>
    </section>

    <section class="how-intro">
      <div>
        <p class="how-kicker">What is BlipBlap eSIM?</p>
        <h2>A digital SIM for travel data, built for quick purchase and simple setup.</h2>
      </div>
      <p>
        An eSIM is a built-in digital SIM supported by most modern phones. BlipBlap lets you choose a destination, buy a prepaid data package, and install it with a QR code. Your physical SIM can stay in your phone for calls or WhatsApp, while the BlipBlap eSIM handles travel internet.
      </p>
    </section>

    <section class="how-highlights">
      <article v-for="item in highlights" :key="item[0]">
        <strong>{{ item[0] }}</strong>
        <p>{{ item[1] }}</p>
      </article>
    </section>

    <section class="how-story">
      <div class="how-story__media">
        <img src="/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-01-300x300.png" alt="Choose an eSIM plan">
        <img src="/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-02-300x300.png" alt="Scan eSIM QR code">
      </div>
      <div class="how-story__copy">
        <p class="how-kicker">How to purchase</p>
        <h2>Pick your plan, pay once, and your eSIM starts moving through the provisioning flow.</h2>
        <div class="how-step-list">
          <div v-for="(step, index) in buyingSteps" :key="step[0]">
            <span>{{ index + 1 }}</span>
            <div>
              <strong>{{ step[0] }}</strong>
              <p>{{ step[1] }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="how-connect">
      <div class="how-connect__copy">
        <p class="how-kicker">How to connect</p>
        <h2>Scan the QR code, add the mobile data plan, and switch it on for data.</h2>
        <p>
          On iPhone, go to Cellular or Mobile Service and add eSIM. On Android, open SIM Manager or Network settings and add eSIM. Scan the BlipBlap QR code, name the line, keep data roaming enabled for that eSIM when needed, then select it for mobile data.
        </p>
      </div>
      <div class="how-demo" aria-label="Animated buying and connection example">
        <div class="how-demo__phone">
          <div class="how-demo__screen">
            <div v-for="frame in phoneFrames" :key="frame[0]" class="how-demo__frame">
              <span>{{ frame[0] }}</span>
              <strong>{{ frame[1] }}</strong>
            </div>
          </div>
        </div>
        <div class="how-demo__signal">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </section>

    <section class="how-final">
      <img src="/images/blipblap/Instant-information.gif" alt="Instant eSIM information">
      <div>
        <p class="how-kicker">Ready when you are</p>
        <h2>Browse country, regional, and worldwide eSIM plans in one place.</h2>
        <a href="/#destinations">Explore all plans</a>
      </div>
    </section>
  </section>
</template>
