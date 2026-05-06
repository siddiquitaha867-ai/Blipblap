<script setup>
defineProps({
  active: {
    type: String,
    default: 'Top Destinations',
  },
});

defineEmits(['update:active']);

const tabs = ['Top Destinations', 'Local eSIMs', 'Regional Packs', 'Worldwide eSIMs'];
</script>

<template>
  <div class="destination-tabs" role="tablist" aria-label="Destination categories">
    <button
      v-for="tab in tabs"
      :key="tab"
      type="button"
      :class="{ active: tab === active }"
      @click="$emit('update:active', tab)"
    >
      {{ tab }}
    </button>
  </div>
</template>
