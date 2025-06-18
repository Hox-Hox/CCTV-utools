<script setup>
import { ref } from 'vue';

const emit = defineEmits(['search']);
const query = ref('');

const handleInput = (event) => {
  query.value = event.target.value;
  emit('search', query.value);
};

const clearSearch = () => {
  query.value = '';
  emit('search', '');
};
</script>

<template>
  <div class="search-bar">
    <div class="search-input-wrapper">
      <i class="search-icon">🔍</i>
      <input
        type="text"
        class="search-input"
        placeholder="搜索频道..."
        v-model="query"
        @input="handleInput"
      />
      <button v-if="query" class="clear-btn" @click="clearSearch">✕</button>
    </div>
  </div>
</template>

<style scoped>
.search-bar {
  display: flex;
  align-items: center;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background-color: #333;
  border-radius: 20px;
  width: 220px;
  height: 36px;
  transition: width 0.3s, background-color 0.3s;
}

.search-input-wrapper:focus-within {
  background-color: #444;
  width: 250px;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: #999;
  font-size: 14px;
  pointer-events: none;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  height: 100%;
  padding: 0 36px 0 35px;
  color: #fff;
  font-size: 14px;
  outline: none;
  width: 100%;
}

.search-input::placeholder {
  color: #999;
}

.clear-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  font-size: 12px;
  border-radius: 50%;
  padding: 0;
  transition: background-color 0.2s;
}

.clear-btn:hover {
  background-color: #555;
  color: #fff;
}
</style> 