<script setup>
import { ref, computed, onMounted, watch, defineExpose } from 'vue';
import { getAllChannels, getAllCategories, getChannelsByCategory } from '../services/channelService';
import { VideoPlay, Star, Delete, StarFilled } from '@element-plus/icons-vue';
import { ElMessage, ElPopconfirm } from 'element-plus';

const emit = defineEmits(['select-channel', 'favorites-change']);

// 状态
const channels = ref([]);
const categories = ref([]);
const activeCategory = ref('');
const isLoading = ref(false);
const favoriteChannels = ref([]);

// 计算属性
const filteredChannels = computed(() => {
  if (activeCategory.value === 'favorites') {
    return favoriteChannels.value;
  }
  if (!activeCategory.value) return [];
  return channels.value.filter(channel => channel.category_name === activeCategory.value);
});

// 判断频道是否已收藏
const isChannelFavorite = (channelId) => {
  return favoriteChannels.value.some(channel => channel.id === channelId);
};

// 方法
const loadChannels = async () => {
  isLoading.value = true;
  try {
    // 获取所有频道
    const allChannels = await getAllChannels();
    channels.value = allChannels;
    
    // 获取所有分类
    const allCategories = await getAllCategories();
    categories.value = allCategories;
    
    // 加载收藏的频道
    loadFavoriteChannels();
    
    // 默认显示第一个分类
    if (allCategories.length > 0 && !activeCategory.value) {
      activeCategory.value = allCategories[0];
    }
  } catch (error) {
    console.error('加载频道失败:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleCategoryChange = (tab) => {
  activeCategory.value = tab.props.name;
};

const selectChannel = (channel) => {
  emit('select-channel', channel);
};

// 保存收藏频道到uTools
const saveFavoriteChannels = () => {
  try {
    const serializableFavorites = JSON.parse(JSON.stringify(favoriteChannels.value));
    
    if (window.utools) {
      window.utools.dbStorage.setItem('cctv-favorite-channels', serializableFavorites);
    } else {
      localStorage.setItem('cctv-favorite-channels', JSON.stringify(serializableFavorites));
    }
    
    // 通知父组件收藏变化
    emit('favorites-change', favoriteChannels.value);
    
    return true;
  } catch (error) {
    console.error('保存收藏频道失败:', error);
    ElMessage({
      message: '保存收藏频道失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
    return false;
  }
};

// 从uTools加载收藏频道
const loadFavoriteChannels = () => {
  try {
    let savedFavorites = null;
    
    if (window.utools) {
      savedFavorites = window.utools.dbStorage.getItem('cctv-favorite-channels');
    } else {
      const favoritesJson = localStorage.getItem('cctv-favorite-channels');
      if (favoritesJson) {
        savedFavorites = JSON.parse(favoritesJson);
      }
    }
    
    if (savedFavorites && Array.isArray(savedFavorites)) {
      favoriteChannels.value = savedFavorites;
      
      // 通知父组件收藏变化
      emit('favorites-change', favoriteChannels.value);
    }
  } catch (error) {
    console.error('加载收藏频道失败:', error);
    ElMessage({
      message: '加载收藏频道失败',
      type: 'error',
      duration: 2000
    });
  }
};

// 添加频道到收藏
const addToFavorites = (event, channel) => {
  // 阻止事件冒泡，避免触发频道选择
  event.stopPropagation();
  
  // 检查频道是否已经在收藏中
  if (isChannelFavorite(channel.id)) {
    ElMessage({
      message: '该频道已在收藏列表中',
      type: 'warning',
      duration: 2000
    });
    return;
  }
  
  // 添加到收藏
  favoriteChannels.value.push({ ...channel });
  
  // 保存到本地存储
  saveFavoriteChannels();
  
  ElMessage({
    message: `已收藏: ${channel.name}`,
    type: 'success',
    duration: 2000
  });
};

// 从收藏中移除频道
const removeFromFavorites = (event, channel) => {
  // 阻止事件冒泡，避免触发频道选择
  event.stopPropagation();
  
  // 查找并移除频道
  const index = favoriteChannels.value.findIndex(c => c.id === channel.id);
  if (index !== -1) {
    favoriteChannels.value.splice(index, 1);
    
    // 保存到本地存储
    saveFavoriteChannels();
    
    ElMessage({
      message: `已取消收藏: ${channel.name}`,
      type: 'success',
      duration: 2000
    });
  }
};

// 切换到收藏标签页
const switchToFavorites = () => {
  activeCategory.value = 'favorites';
};

// 生命周期钩子
onMounted(async () => {
  await loadChannels();
});

// 监听收藏列表变化
watch(favoriteChannels, (newFavorites) => {
  // 通知父组件收藏变化
  emit('favorites-change', newFavorites);
}, { deep: true });

// 暴露方法给父组件
defineExpose({
  switchToFavorites
});
</script>

<template>
  <div class="channel-list-container">
    <div v-if="isLoading" class="loading-container">
      <el-skeleton :rows="8" animated />
    </div>
    
    <div v-else class="scrollable-content">
      <el-tabs 
        v-model="activeCategory" 
        @tab-click="handleCategoryChange"
        class="category-tabs"
        :stretch="false"
      >
        <!-- 收藏频道分类 -->
        <el-tab-pane 
          key="favorites" 
          label="我的收藏" 
          name="favorites"
        >
          <div v-if="favoriteChannels.length === 0" class="empty-favorites">
            <el-icon class="empty-icon"><Star /></el-icon>
            <p>暂无收藏频道</p>
            <p class="empty-tip">请在频道列表中点击星标添加收藏</p>
          </div>
          
          <div v-else class="channels-grid">
            <div 
              v-for="channel in favoriteChannels" 
              :key="channel.id" 
              class="channel-item favorite-item"
              @click="selectChannel(channel)"
            >
              <div class="channel-icon favorite-icon">
                <el-icon><VideoPlay /></el-icon>
              </div>
              <div class="channel-info">
                <div class="channel-name">{{ channel.name }}</div>
                <div class="channel-category">{{ channel.category_name }}</div>
              </div>
              
              <el-popconfirm
                title="确定要取消收藏此频道吗?"
                @confirm="removeFromFavorites($event, channel)"
                width="200"
                confirm-button-text="确定"
                cancel-button-text="取消"
              >
                <template #reference>
                  <div class="favorite-action" @click.stop>
                    <el-icon><Delete /></el-icon>
                  </div>
                </template>
              </el-popconfirm>
            </div>
          </div>
        </el-tab-pane>
        
        <!-- 正常频道分类 -->
        <el-tab-pane 
          v-for="category in categories" 
          :key="category" 
          :label="category" 
          :name="category"
        >
          <div class="channels-grid">
            <div 
              v-for="channel in filteredChannels" 
              :key="channel.id" 
              class="channel-item"
              @click="selectChannel(channel)"
            >
              <div class="channel-icon">
                <el-icon><VideoPlay /></el-icon>
              </div>
              <div class="channel-info">
                <div class="channel-name">{{ channel.name }}</div>
              </div>
              
              <!-- 收藏按钮 -->
              <div 
                class="favorite-action" 
                @click.stop
                :class="{ 'is-favorite': isChannelFavorite(channel.id) }"
              >
                <el-icon v-if="isChannelFavorite(channel.id)" @click="removeFromFavorites($event, channel)">
                  <StarFilled />
                </el-icon>
                <el-icon v-else @click="addToFavorites($event, channel)">
                  <Star />
                </el-icon>
              </div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<style scoped>
.channel-list-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 16px;
  background-color: var(--background-color);
  box-sizing: border-box;
}

.scrollable-content {
  flex: 1;
  overflow-y: auto;
  position: relative;
  min-height: 0; /* 确保flex子项可以正常滚动 */
  padding: 0 2px; /* 为滚动条留出空间 */
}

/* 添加分类标签横向滚动样式 */
:deep(.el-tabs__nav-wrap) {
  overflow-x: auto;
  overflow-y: hidden;
  margin-bottom: -1px; /* 修复底部边框重叠问题 */
}

:deep(.el-tabs__nav-scroll) {
  overflow: visible;
  white-space: nowrap;
}

:deep(.el-tabs__nav) {
  white-space: nowrap;
  display: inline-flex;
  float: none;
  padding-bottom: 2px; /* 为滚动条留出空间 */
}

:deep(.el-tabs__item) {
  flex-shrink: 0;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
  flex-shrink: 0;
  overflow-x: auto;
  overflow-y: hidden;
  border-bottom: none;
}

/* 自定义滚动条样式 */
:deep(.el-tabs__header)::-webkit-scrollbar {
  height: 4px;
}

:deep(.el-tabs__header)::-webkit-scrollbar-thumb {
  background-color: var(--primary-light);
  border-radius: 2px;
}

:deep(.el-tabs__header)::-webkit-scrollbar-track {
  background-color: var(--background-color);
}

:deep(.el-tabs__content) {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
  min-height: 0; /* 确保flex子项可以正常滚动 */
}

/* 自定义标签内容区滚动条 */
:deep(.el-tabs__content)::-webkit-scrollbar {
  width: 5px;
}

:deep(.el-tabs__content)::-webkit-scrollbar-thumb {
  background-color: var(--primary-light);
  border-radius: 3px;
}

:deep(.el-tabs__content)::-webkit-scrollbar-track {
  background-color: transparent;
}

.channels-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 16px;
  padding-bottom: 24px;
  animation: fadeIn 0.4s ease-out;
}

.channel-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-radius: var(--border-radius-sm);
  background-color: var(--card-bg);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none; /* 防止拖动时选中文本 */
  position: relative;
  box-shadow: var(--box-shadow);
  border: 1px solid transparent;
  overflow: hidden;
}

.channel-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, transparent, rgba(255, 255, 255, 0.05));
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: 0;
  pointer-events: none;
}

.channel-item:hover {
  transform: translateY(-3px);
  box-shadow: var(--box-shadow-hover);
  border-color: var(--primary-color);
}

.channel-item:hover::before {
  opacity: 1;
}

.favorite-item {
  border-left: 4px solid var(--warning-color);
  background: linear-gradient(to right, rgba(255, 170, 51, 0.05), transparent 20%);
}

.channel-icon {
  font-size: 24px;
  color: var(--primary-color);
  margin-right: 16px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--primary-bg);
  width: 48px;
  height: 48px;
  border-radius: 50%;
  transition: all 0.3s;
  position: relative;
  z-index: 1;
  box-shadow: 0 2px 8px rgba(62, 132, 248, 0.2);
}

.favorite-icon {
  color: var(--warning-color);
  background-color: rgba(255, 170, 51, 0.1);
  box-shadow: 0 2px 8px rgba(255, 170, 51, 0.2);
}

.channel-item:hover .channel-icon {
  background-color: var(--primary-color);
  color: white;
  transform: scale(1.05);
}

.favorite-item:hover .channel-icon {
  background-color: var(--warning-color);
}

.channel-info {
  flex: 1;
  min-width: 0; /* 确保flex子项可以正常收缩 */
  position: relative;
  z-index: 1;
}

.channel-name {
  font-weight: 600;
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 1.05rem;
  color: var(--text-color);
  transition: color 0.3s;
}

.channel-category {
  font-size: 0.85rem;
  color: var(--secondary-text-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 0.9;
}

.favorite-action {
  padding: 10px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--secondary-text-color);
  cursor: pointer;
  transition: all 0.3s;
  opacity: 0.7;
  margin-left: 10px;
  background-color: transparent;
  position: relative;
  z-index: 1;
}

.favorite-action:hover {
  background-color: var(--primary-bg);
  color: var(--primary-color);
  opacity: 1;
  transform: scale(1.1);
}

.favorite-action.is-favorite {
  color: var(--warning-color);
  opacity: 1;
}

.loading-container {
  flex: 1;
  padding: 20px;
}

/* 添加分类标签页样式 */
.category-tabs {
  height: 100%;
}

/* 自定义标签样式 */
:deep(.el-tabs__item) {
  padding: 0 20px;
  height: 40px;
  line-height: 40px;
  min-width: 100px;
  text-align: center;
  font-weight: 500;
  border-radius: var(--border-radius-sm) var(--border-radius-sm) 0 0;
  transition: all 0.3s;
  font-size: 0.95rem;
}

:deep(.el-tabs__item):hover {
  color: var(--primary-color) !important;
  background-color: var(--primary-bg);
}

:deep(.el-tabs__item.is-active) {
  font-weight: 600;
  color: var(--primary-color) !important;
  position: relative;
}

:deep(.el-tabs__item.is-active)::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 30px;
  height: 3px;
  background-color: var(--primary-color);
  border-radius: 3px 3px 0 0;
}

:deep(.el-tabs__active-bar) {
  display: none;
}

/* 美化滚动条 */
:deep(.el-tabs__header)::-webkit-scrollbar {
  height: 4px;
}

:deep(.el-tabs__header)::-webkit-scrollbar-thumb {
  background-color: var(--primary-light);
  border-radius: 2px;
}

:deep(.el-tabs__header)::-webkit-scrollbar-thumb:hover {
  background-color: var(--primary-color);
}

:deep(.el-tabs__header)::-webkit-scrollbar-track {
  background-color: var(--background-color);
}

/* 空收藏样式 */
.empty-favorites {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: var(--secondary-text-color);
  text-align: center;
  padding: 20px;
  animation: fadeIn 0.6s ease-out;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 20px;
  color: var(--warning-color);
  opacity: 0.6;
  animation: pulse 2s infinite ease-in-out;
}

.empty-favorites p {
  margin: 6px 0;
  font-size: 1.1rem;
}

.empty-tip {
  font-size: 0.9rem;
  opacity: 0.8;
  margin-top: 8px;
  max-width: 220px;
  line-height: 1.5;
}

/* 添加动画关键帧 */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}
</style> 