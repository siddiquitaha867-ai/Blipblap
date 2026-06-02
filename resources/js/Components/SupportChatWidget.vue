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
    <button type="button" class="support-chat-toggle" @click="open = !open">
      <span>AI Support</span>
    </button>

    <section v-if="open" class="support-chat-panel">
      <header class="support-chat-header">
        <div>
          <strong>BlipBlap AI Support</strong>
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
