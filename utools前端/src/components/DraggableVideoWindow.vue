<template>
  <div
    v-if="visible"
    class="draggable-container"
    :style="containerStyle"
    :class="{ 
      'on-top': isOnTop, 
      'maximized': isMaximized,
      'floating': isFloating,
      'fixed': !isFloating
    }"
    ref="container"
  >
    <div class="draggable-header" @mousedown="startDrag">
      <div class="channel-title">{{ channel.name }}</div>
      <div class="window-controls">
        <el-button 
          v-if="isFloating"
          size="small" 
          circle 
          @click="togglePin" 
          :type="isPinned ? 'primary' : 'default'"
          :icon="isPinned ? 'PushPin' : 'Position'"
        />
        <el-button 
          v-if="isFloating"
          size="small" 
          circle 
          @click="toggleMaximize" 
          :icon="isMaximized ? 'Compress' : 'FullScreen'" 
        />
        <el-button 
          size="small" 
          circle 
          type="danger" 
          @click="close" 
          icon="Close" 
        />
      </div>
    </div>
    <div class="video-container">
      <VideoPlayer 
        :src="channel.url" 
        :autoplay="autoPlay" 
        :volume="volume"
        @volume-change="handleVolumeChange"
      />
    </div>
    <div 
      class="resize-handle" 
      @mousedown="startResize"
      v-if="isFloating && !isMaximized"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import VideoPlayer from './VideoPlayer.vue';

const props = defineProps({
  channel: {
    type: Object,
    required: true
  },
  isOnTop: {
    type: Boolean,
    default: false
  },
  initialPosition: {
    type: Object,
    default: () => ({ x: 100, y: 100 })
  },
  initialSize: {
    type: Object,
    default: () => ({ width: 480, height: 270 })
  },
  isFloating: {
    type: Boolean,
    default: true
  },
  autoPlay: {
    type: Boolean,
    default: true
  },
  defaultVolume: {
    type: Number,
    default: 50
  }
});

const emit = defineEmits(['close', 'toggle-pin', 'bring-to-top', 'volume-change']);

const visible = ref(true);
const position = ref({ ...props.initialPosition });
const size = ref({ ...props.initialSize });
const isMaximized = ref(false);
const isPinned = ref(false);
const isDragging = ref(false);
const isResizing = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const container = ref(null);
const originalSize = ref(null);
const originalPosition = ref(null);
const volume = ref(props.defaultVolume);

// 容器样式
const containerStyle = computed(() => {
  if (isMaximized.value) {
    return {
      left: '0',
      top: '0',
      width: '100%',
      height: '100%',
      zIndex: props.isOnTop ? '1000' : '10',
      position: 'fixed'
    };
  }
  
  return {
    left: `${position.value.x}px`,
    top: `${position.value.y}px`,
    width: `${size.value.width}px`,
    height: `${size.value.height}px`,
    zIndex: props.isOnTop ? '1000' : '10',
    position: props.isFloating ? 'fixed' : 'absolute'
  };
});

// 开始拖拽
const startDrag = (event) => {
  if (isMaximized.value || !props.isFloating) return;
  
  isDragging.value = true;
  dragOffset.value = {
    x: event.clientX - position.value.x,
    y: event.clientY - position.value.y
  };
  
  // 防止页面滚动
  document.body.classList.add('scrolling-disabled');
  
  emit('bring-to-top');
  event.preventDefault();
  event.stopPropagation();
};

// 开始调整大小
const startResize = (event) => {
  if (!props.isFloating) return;
  
  isResizing.value = true;
  dragOffset.value = {
    x: event.clientX,
    y: event.clientY
  };
  originalSize.value = { ...size.value };
  
  // 防止页面滚动
  document.body.classList.add('scrolling-disabled');
  
  emit('bring-to-top');
  event.preventDefault();
  event.stopPropagation();
};

// 处理拖拽
const onDrag = (event) => {
  if (!isDragging.value || !props.isFloating) return;
  
  const newX = event.clientX - dragOffset.value.x;
  const newY = event.clientY - dragOffset.value.y;
  
  // 确保窗口不会被拖出视图
  const maxX = window.innerWidth - size.value.width;
  const maxY = window.innerHeight - size.value.height;
  
  position.value = {
    x: Math.max(0, Math.min(newX, maxX)),
    y: Math.max(0, Math.min(newY, maxY))
  };
  
  event.preventDefault();
  event.stopPropagation();
};

// 处理调整大小
const onResize = (event) => {
  if (!isResizing.value || !props.isFloating) return;
  
  const deltaX = event.clientX - dragOffset.value.x;
  const deltaY = event.clientY - dragOffset.value.y;
  
  // 保持16:9比例
  const aspectRatio = 16 / 9;
  
  // 通过拖动调整宽度，高度按比例调整
  const newWidth = Math.max(320, originalSize.value.width + deltaX);
  const newHeight = Math.round(newWidth / aspectRatio);
  
  size.value = {
    width: newWidth,
    height: newHeight
  };
  
  event.preventDefault();
  event.stopPropagation();
};

// 停止拖拽和调整大小
const stopDragAndResize = () => {
  isDragging.value = false;
  isResizing.value = false;
  
  // 恢复页面滚动
  document.body.classList.remove('scrolling-disabled');
};

// 切换最大化
const toggleMaximize = () => {
  if (!props.isFloating) return;
  
  if (!isMaximized.value) {
    // 保存当前大小和位置
    originalSize.value = { ...size.value };
    originalPosition.value = { ...position.value };
    isMaximized.value = true;
  } else {
    // 恢复之前的大小和位置
    size.value = { ...originalSize.value };
    position.value = { ...originalPosition.value };
    isMaximized.value = false;
  }
};

// 切换置顶
const togglePin = () => {
  isPinned.value = !isPinned.value;
  emit('toggle-pin', isPinned.value);
};

// 关闭窗口
const close = () => {
  visible.value = false;
  emit('close');
};

// 监听isFloating属性变化
watch(() => props.isFloating, (newValue) => {
  // 如果窗口由悬浮变为固定，而且正在最大化，则恢复原始大小
  if (!newValue && isMaximized.value) {
    isMaximized.value = false;
    if (originalSize.value && originalPosition.value) {
      size.value = { ...originalSize.value };
      position.value = { ...originalPosition.value };
    }
  }
});

// 监听initialSize属性变化
watch(() => props.initialSize, (newSize) => {
  // 如果窗口未调整过大小，则应用新的初始大小
  if (!originalSize.value && !isMaximized.value) {
    size.value = { ...newSize };
  }
});

// 处理音量变化
const handleVolumeChange = (newVolume) => {
  volume.value = newVolume;
  emit('volume-change', { channelId: props.channel.id, volume: newVolume });
};

// 添加鼠标事件监听
onMounted(() => {
  window.addEventListener('mousemove', onDrag);
  window.addEventListener('mousemove', onResize);
  window.addEventListener('mouseup', stopDragAndResize);
});

// 移除鼠标事件监听
onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onDrag);
  window.removeEventListener('mousemove', onResize);
  window.removeEventListener('mouseup', stopDragAndResize);
});
</script>

<style scoped>
.draggable-container {
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2), 0 3px 6px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  background-color: var(--card-bg);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  border: 1px solid var(--border-color);
  animation: fadeIn 0.3s ease-out;
}

.draggable-container.floating {
  position: fixed;
}

.draggable-container.fixed {
  position: absolute;
  width: 100% !important;
  height: 100% !important;
  left: 0 !important;
  top: 0 !important;
  border-radius: 0;
  border: none;
}

.draggable-container.on-top {
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25), 0 8px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000 !important;
}

.draggable-container.maximized {
  border-radius: 0;
  border: none;
}

.draggable-header {
  padding: 12px 16px;
  background-color: var(--card-bg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: move;
  user-select: none;
  border-bottom: 1px solid var(--border-color);
  transition: background-color 0.3s;
}

.draggable-header:hover {
  background-color: var(--hover-bg);
}

.fixed .draggable-header {
  cursor: default;
}

.channel-title {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 1rem;
  color: var(--text-color);
  letter-spacing: 0.3px;
  display: flex;
  align-items: center;
}

.channel-title::before {
  content: '';
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: var(--primary-color);
  border-radius: 50%;
  margin-right: 10px;
  box-shadow: 0 0 5px var(--primary-light);
}

.window-controls {
  display: flex;
  gap: 8px;
}

.window-controls .el-button {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.window-controls .el-button:hover {
  transform: scale(1.1);
  background-color: var(--hover-bg);
}

.window-controls .el-button.is-circle {
  border: none;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.video-container {
  flex: 1;
  background-color: #000;
  position: relative;
  overflow: hidden;
}

.video-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1), transparent);
  z-index: 1;
}

.resize-handle {
  position: absolute;
  width: 24px;
  height: 24px;
  bottom: 0;
  right: 0;
  cursor: nwse-resize;
  opacity: 0.5;
  transition: opacity 0.2s;
}

.resize-handle:hover {
  opacity: 1;
}

.resize-handle::after {
  content: '';
  position: absolute;
  right: 8px;
  bottom: 8px;
  width: 10px;
  height: 10px;
  border-right: 2px solid rgba(255, 255, 255, 0.9);
  border-bottom: 2px solid rgba(255, 255, 255, 0.9);
  box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.2);
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style> 