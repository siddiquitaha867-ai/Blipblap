<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  hidden: {
    type: Boolean,
    default: false,
  },
});

const open = ref(false);
const loading = ref(false);
const message = ref('');
const suggestions = ref([
  'How do I install my eSIM?',
  'Where can I find My eSIMs?',
  'How does checkout work?',
]);
const messages = ref([
  {
    role: 'assistant',
    text: 'Hi, I am BlipBlap support. I can help with plans, checkout, installation, My eSIMs, and common support questions.',
  },
]);

const visible = computed(() => !props.hidden);

const sendMessage = async (preset = '') => {
  const text = (preset || message.value).trim();

  if (!text || loading.value) {
    return;
  }

  messages.value.push({ role: 'user', text });
  message.value = '';
  loading.value = true;

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch('/support/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ message: text }),
    });

    const payload = await response.json();
    messages.value.push({
      role: 'assistant',
      text: payload.answer || 'I could not generate a reply right now. Please try again.',
    });
    suggestions.value = Array.isArray(payload.suggestions) ? payload.suggestions : [];
  } catch {
    messages.value.push({
      role: 'assistant',
      text: 'Support chat is temporarily unavailable. Please try again in a moment.',
    });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div v-if="visible" class="support-chat">
    <button v-if="!open" type="button" class="support-chat-toggle" aria-label="Ask AI support" @click="open = true">
      <span class="support-chat-toggle__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
          <path d="M12 3.5v2" />
          <path d="M7.5 7.5h9A3.5 3.5 0 0 1 20 11v3.5a3.5 3.5 0 0 1-3.5 3.5h-9A3.5 3.5 0 0 1 4 14.5V11a3.5 3.5 0 0 1 3.5-3.5Z" />
          <path d="M9 12h.01" />
          <path d="M15 12h.01" />
          <path d="M9.5 15c1.25.8 3.75.8 5 0" />
          <path d="M3 13h1" />
          <path d="M20 13h1" />
        </svg>
      </span>
    </button>

    <section v-if="open" class="support-chat-panel">
      <header class="support-chat-header">
        <div>
          <strong>Ask AI</strong>
          <small>Plans, checkout, install help, and FAQs</small>
        </div>
        <button type="button" class="support-chat-close" @click="open = false">Close</button>
      </header>

      <div class="support-chat-messages">
        <article
          v-for="(item, index) in messages"
          :key="`${item.role}-${index}`"
          class="support-chat-message"
          :class="`is-${item.role}`"
        >
          {{ item.text }}
        </article>
      </div>

      <div v-if="suggestions.length" class="support-chat-suggestions">
        <button
          v-for="suggestion in suggestions"
          :key="suggestion"
          type="button"
          @click="sendMessage(suggestion)"
        >
          {{ suggestion }}
        </button>
      </div>

      <form class="support-chat-form" @submit.prevent="sendMessage()">
        <input
          v-model="message"
          type="text"
          maxlength="500"
          placeholder="Ask about installation, checkout, usage..."
        />
        <button type="submit" :disabled="loading">{{ loading ? 'Sending...' : 'Send' }}</button>
      </form>
    </section>
  </div>
</template>
