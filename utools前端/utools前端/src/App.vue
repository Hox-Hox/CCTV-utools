<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import ChannelList from './components/ChannelList.vue';
import DraggableVideoWindow from './components/DraggableVideoWindow.vue';
import SettingsPanel from './components/SettingsPanel.vue';
import { getAllChannels } from './services/channelService';
import { Monitor, Setting, Star } from '@element-plus/icons-vue';

// 初始化状态
const activeWindows = ref([]);
const topWindowId = ref(null);
const pinnedWindows = ref([]);
const isInitialized = ref(false);
const isDarkMode = ref(true);
const settingsRef = ref(null);
const isLoading = ref(false);
const favoriteChannels = ref([]);

// 应用设置
const appSettings = reactive({
  darkMode: true,
  compactMode: false,
  multipleWindows: true,
  floatingWindows: true,
  autoPlay: true,
  defaultVolume: 50,
  defaultWindowSize: 'medium',
  channelVolumes: {} // 保存每个频道的音量设置
});

// 引用组件
const channelListRef = ref(null);

// 加载状态切换
const setLoading = (state) => {
  isLoading.value = state;
  // 添加加载状态类到body
  if (state) {
    document.body.classList.add('app-loading');
  } else {
    document.body.classList.remove('app-loading');
  }
};

// 基于偏好获取窗口大小
const getWindowSizeByPreference = (preference) => {
  switch (preference) {
    case 'small':
      return { width: 320, height: 180 }; // 16:9 比例
    case 'large':
      return { width: 640, height: 360 }; // 16:9 比例
    case 'medium':
    default:
      return { width: 480, height: 270 }; // 16:9 比例
  }
};

// 选择频道并打开窗口
const handleSelectChannel = (channel) => {
  // 设置短暂加载状态
  setLoading(true);
  
  setTimeout(() => {
    // 检查是否允许多窗口
    if (!appSettings.multipleWindows && activeWindows.value.length > 0) {
      // 如果不允许多窗口，替换现有窗口
      const firstWindow = activeWindows.value[0];
      firstWindow.channel = { ...channel }; // 创建新的对象以触发响应式更新
      bringWindowToTop(firstWindow.id);
      
      setLoading(false);
      return;
    }
    
    // 检查窗口是否已经打开
    const existingWindowIndex = activeWindows.value.findIndex(w => w.channel.id === channel.id);
    
    if (existingWindowIndex !== -1) {
      // 如果窗口已打开，将其置顶
      bringWindowToTop(activeWindows.value[existingWindowIndex].id);
      setLoading(false);
      return;
    }
    
    // 创建新窗口
    const windowId = Date.now().toString();
    
    // 计算新窗口的位置，避免完全重叠
    const offset = activeWindows.value.length * 30;
    const position = {
      x: 100 + offset,
      y: 100 + offset
    };
    
    // 获取窗口大小
    const size = getWindowSizeByPreference(appSettings.defaultWindowSize);
    
    // 添加新窗口
    activeWindows.value.push({
      id: windowId,
      channel: { ...channel }, // 使用对象副本确保响应式更新
      position,
      size
    });
    
    // 将新窗口置顶
    bringWindowToTop(windowId);
    
    setLoading(false);
  }, 200); // 短暂延迟以便UI可以显示加载状态
};

// 将窗口置顶
const bringWindowToTop = (windowId) => {
  topWindowId.value = windowId;
};

// 关闭窗口
const closeWindow = (windowId) => {
  const index = activeWindows.value.findIndex(w => w.id === windowId);
  if (index !== -1) {
    activeWindows.value.splice(index, 1);
    
    // 如果关闭的是当前置顶窗口，将最后一个窗口置顶
    if (topWindowId.value === windowId && activeWindows.value.length > 0) {
      topWindowId.value = activeWindows.value[activeWindows.value.length - 1].id;
    }
    
    // 如果窗口已被置顶，从置顶列表移除
    const pinnedIndex = pinnedWindows.value.indexOf(windowId);
    if (pinnedIndex !== -1) {
      pinnedWindows.value.splice(pinnedIndex, 1);
    }
  }
};

// 切换窗口置顶状态
const toggleWindowPin = (windowId, isPinned) => {
  if (isPinned) {
    // 添加到置顶列表
    if (!pinnedWindows.value.includes(windowId)) {
      pinnedWindows.value.push(windowId);
    }
  } else {
    // 从置顶列表移除
    const index = pinnedWindows.value.indexOf(windowId);
    if (index !== -1) {
      pinnedWindows.value.splice(index, 1);
    }
  }
  
  // 保存置顶窗口列表
  savePinnedWindows();
};

// 保存置顶窗口列表
const savePinnedWindows = () => {
  try {
    // 创建可序列化的窗口列表
    const serializablePinned = JSON.parse(JSON.stringify(pinnedWindows.value));
    
    if (window.utools) {
      window.utools.dbStorage.setItem('cctv-pinned-windows', serializablePinned);
    } else {
      localStorage.setItem('cctv-pinned-windows', JSON.stringify(serializablePinned));
    }
    return true;
  } catch (error) {
    console.error('保存置顶窗口列表失败:', error);
    return false;
  }
};

// 加载置顶窗口列表
const loadPinnedWindows = () => {
  let savedPinned = null;
  
  if (window.utools) {
    savedPinned = window.utools.dbStorage.getItem('cctv-pinned-windows');
  } else {
    const pinnedJson = localStorage.getItem('cctv-pinned-windows');
    if (pinnedJson) {
      try {
        savedPinned = JSON.parse(pinnedJson);
      } catch (e) {
        console.error('解析置顶窗口列表出错:', e);
      }
    }
  }
  
  if (savedPinned && Array.isArray(savedPinned)) {
    pinnedWindows.value = savedPinned;
  }
};

// 处理频道音量变化
const handleVolumeChange = ({ channelId, volume }) => {
  if (channelId) {
    // 保存特定频道的音量设置
    appSettings.channelVolumes[channelId] = volume;
    
    // 保存到本地存储
    saveChannelVolumes();
  }
};

// 保存频道音量设置到本地存储
const saveChannelVolumes = () => {
  try {
    // 创建一个纯粹的数据对象
    const serializableVolumes = JSON.parse(JSON.stringify(appSettings.channelVolumes));
    
    if (window.utools) {
      // 使用uTools API保存设置
      window.utools.dbStorage.setItem('cctv-channel-volumes', serializableVolumes);
    } else {
      // 回退到localStorage
      localStorage.setItem('cctv-channel-volumes', JSON.stringify(serializableVolumes));
    }
    return true;
  } catch (error) {
    console.error('保存频道音量设置失败:', error);
    ElMessage({
      message: '保存频道音量设置失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
    return false;
  }
};

// 从本地存储加载频道音量设置
const loadChannelVolumes = () => {
  let savedVolumes = null;
  
  if (window.utools) {
    // 使用uTools API获取设置
    savedVolumes = window.utools.dbStorage.getItem('cctv-channel-volumes');
  } else {
    // 回退到localStorage
    const volumesJson = localStorage.getItem('cctv-channel-volumes');
    if (volumesJson) {
      try {
        savedVolumes = JSON.parse(volumesJson);
      } catch (e) {
        console.error('解析频道音量设置出错:', e);
      }
    }
  }
  
  if (savedVolumes) {
    // 合并保存的音量设置
    Object.assign(appSettings.channelVolumes, savedVolumes);
  }
};

// 打开设置面板
const openSettings = () => {
  if (settingsRef.value) {
    settingsRef.value.showSettings();
  }
};

// 处理设置更新
const handleSettingsUpdate = ({ all, key, value, settings }) => {
  try {
    if (all && settings) {
      // 更新所有设置
      Object.assign(appSettings, settings);
    } else if (key) {
      // 更新单个设置
      appSettings[key] = value;
    }
    
    // 应用暗色模式
    isDarkMode.value = appSettings.darkMode;
    
    // 如果更改了默认音量，可能需要更新所有活动窗口
    if (key === 'defaultVolume') {
      // 更新没有自定义音量的窗口
      activeWindows.value.forEach(window => {
        if (!appSettings.channelVolumes[window.channel.id]) {
          // 重新渲染窗口以应用新的默认音量
          window.channel = { ...window.channel };
        }
      });
    }
    
    // 保存设置
    saveAppSettings();
  } catch (error) {
    console.error('设置更新失败:', error);
    ElMessage({
      message: '设置更新失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
  }
};

// 创建可序列化的设置对象
const createSerializableSettings = () => {
  return JSON.parse(JSON.stringify({
    darkMode: appSettings.darkMode,
    compactMode: appSettings.compactMode,
    multipleWindows: appSettings.multipleWindows,
    floatingWindows: appSettings.floatingWindows,
    autoPlay: appSettings.autoPlay,
    defaultVolume: appSettings.defaultVolume,
    defaultWindowSize: appSettings.defaultWindowSize,
    // 单独处理对象类型的属性
    channelVolumes: { ...appSettings.channelVolumes }
  }));
};

// 保存应用设置
const saveAppSettings = () => {
  try {
    // 创建一个纯粹的数据对象，避免代理对象的序列化问题
    const serializableSettings = createSerializableSettings();
    
    if (window.utools) {
      window.utools.dbStorage.setItem('cctv-app-settings', serializableSettings);
    } else {
      localStorage.setItem('cctv-app-settings', JSON.stringify(serializableSettings));
    }
    return true;
  } catch (error) {
    console.error('保存应用设置失败:', error);
    ElMessage({
      message: '保存应用设置失败: ' + error.message,
      type: 'error',
      duration: 3000
    });
    return false;
  }
};

// 加载应用设置
const loadAppSettings = () => {
  let savedSettings = null;
  
  if (window.utools) {
    savedSettings = window.utools.dbStorage.getItem('cctv-app-settings');
  } else {
    const settingsJson = localStorage.getItem('cctv-app-settings');
    if (settingsJson) {
      try {
        savedSettings = JSON.parse(settingsJson);
      } catch (e) {
        console.error('解析应用设置出错:', e);
      }
    }
  }
  
  if (savedSettings) {
    Object.assign(appSettings, savedSettings);
    isDarkMode.value = appSettings.darkMode;
  }
};

// 加载收藏频道
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
    }
  } catch (error) {
    console.error('加载收藏频道失败:', error);
  }
};

// 处理收藏列表变化
const handleFavoritesChange = (newFavorites) => {
  favoriteChannels.value = newFavorites;
};

// 快速切换到收藏频道
const openFavorites = () => {
  if (channelListRef.value) {
    channelListRef.value.switchToFavorites();
  }
};

// uTools插件初始化
const initializeUTools = () => {
  if (window.utools) {
    window.utools.onPluginEnter(({ code }) => {
      isInitialized.value = true;
      console.log('插件已启动，功能代码:', code);
    });
  }
};

// 生命周期钩子
onMounted(() => {
  // 加载所有设置和状态
  initializeUTools();
  loadAppSettings();
  loadChannelVolumes();
  loadPinnedWindows();
  loadFavoriteChannels();
  
  // 监听键盘事件
  window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

// 键盘快捷键处理
const handleKeyDown = (event) => {
  // ESC键关闭当前置顶窗口
  if (event.key === 'Escape' && topWindowId.value) {
    // 如果窗口已置顶，不关闭
    if (!pinnedWindows.value.includes(topWindowId.value)) {
      closeWindow(topWindowId.value);
    }
  }
  
  // Ctrl+, 打开设置
  if (event.ctrlKey && event.key === ',') {
    openSettings();
    event.preventDefault();
  }
  
  // Ctrl+F 快速访问收藏
  if (event.ctrlKey && event.key === 'f') {
    openFavorites();
    event.preventDefault();
  }
};

// 监听appSettings变化，更新现有窗口
watch(() => appSettings.floatingWindows, (newValue) => {
  // 更新所有活动窗口的浮动状态
  activeWindows.value.forEach(window => {
    // 重新渲染窗口以应用新的浮动状态
    window.channel = { ...window.channel };
  });
});

// 是否有活动窗口
const hasActiveWindows = computed(() => {
  return activeWindows.value.length > 0;
});
</script>

<template>
  <div class="app-container" :class="{ 
    'dark-mode': isDarkMode, 
    'compact-mode': appSettings.compactMode,
    'loading': isLoading
  }">
    <header class="app-header">
      <div class="logo">
        <img src="/logo.png" alt="CCTV Logo" class="logo-image" />
        <h1>CCTV直播聚合</h1>
      </div>
      <div class="header-controls">
        <el-tooltip content="我的收藏 (Ctrl + F)" placement="bottom">
          <el-button circle :icon="Star" @click="openFavorites" />
        </el-tooltip>
        <el-tooltip content="设置 (Ctrl + ,)" placement="bottom">
          <el-button circle :icon="Setting" @click="openSettings" />
        </el-tooltip>
      </div>
    </header>
    
    <main class="main-content">
      <aside class="sidebar">
        <ChannelList 
          ref="channelListRef"
          @select-channel="handleSelectChannel"
          @favorites-change="handleFavoritesChange"
        />
      </aside>
      
      <div class="video-area">
        <div v-if="!hasActiveWindows" class="empty-state">
          <el-icon class="empty-icon"><Monitor /></el-icon>
          <h2>请从左侧选择频道开始观看</h2>
          <p>支持多窗口播放，可拖拽调整位置和大小</p>
        </div>
        
        <div v-if="isLoading" class="loading-overlay">
          <div class="loading-spinner"></div>
          <p>加载中...</p>
        </div>
        
        <DraggableVideoWindow
          v-for="window in activeWindows"
          :key="window.id"
          :channel="window.channel"
          :is-on-top="topWindowId === window.id"
          :initial-position="window.position"
          :initial-size="window.size"
          :is-floating="appSettings.floatingWindows"
          :auto-play="appSettings.autoPlay"
          :default-volume="appSettings.channelVolumes[window.channel.id] || appSettings.defaultVolume"
          @close="closeWindow(window.id)"
          @toggle-pin="(isPinned) => toggleWindowPin(window.id, isPinned)"
          @bring-to-top="bringWindowToTop(window.id)"
          @volume-change="handleVolumeChange"
        />
      </div>
    </main>
    
    <SettingsPanel 
      ref="settingsRef"
      @update-settings="handleSettingsUpdate"
    />
  </div>
</template>

<style>
/* 全局样式 */
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
  overflow: hidden;
  font-family: 'Helvetica Neue', Helvetica, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', Arial, sans-serif;
}

#app {
  height: 100%;
  width: 100%;
}

body.app-loading {
  cursor: progress;
}

/* 定义CSS变量 */
:root {
  --primary-color: #3E84F8;
  --primary-light: #5E9BFF;
  --primary-dark: #2D6CD5;
  --primary-bg: rgba(62, 132, 248, 0.08);
  
  --success-color: #42C08A;
  --warning-color: #FFAA33;
  --danger-color: #FF6464;
  --info-color: #8C9AAF;
  
  /* 亮色主题 */
  --light-bg: #F8FAFC;
  --light-card-bg: #FFFFFF;
  --light-text: #2C3E50;
  --light-secondary-text: #5E6C84;
  --light-border: #E5E9F2;
  --light-hover: #F2F6FC;
  
  /* 暗色主题 */
  --dark-bg: #1A202C;
  --dark-card-bg: #2D3748;
  --dark-text: #E2E8F0;
  --dark-secondary-text: #A0AEC0;
  --dark-border: #4A5568;
  --dark-hover: #3A4556;
  
  /* 公共变量 */
  --header-height: 60px;
  --transition-speed: 0.3s;
  --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  --box-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
  --border-radius: 12px;
  --border-radius-sm: 8px;
}

/* 应用主题变量 */
.app-container {
  --background-color: var(--light-bg);
  --card-bg: var(--light-card-bg);
  --text-color: var(--light-text);
  --secondary-text-color: var(--light-secondary-text);
  --border-color: var(--light-border);
  --hover-bg: var(--light-hover);
}

.app-container.dark-mode {
  --background-color: var(--dark-bg);
  --card-bg: var(--dark-card-bg);
  --text-color: var(--dark-text);
  --secondary-text-color: var(--dark-secondary-text);
  --border-color: var(--dark-border);
  --hover-bg: var(--dark-hover);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

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
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
</style>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  background-color: var(--background-color);
  color: var(--text-color);
  transition: background-color var(--transition-speed), color var(--transition-speed);
}

.compact-mode .app-header {
  height: calc(var(--header-height) - 10px);
  padding: 0 16px;
}

.compact-mode .logo h1 {
  font-size: 1.2rem;
}

.compact-mode .logo-image {
  height: 32px;
}

.app-header {
  height: var(--header-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  background-color: var(--card-bg);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
  z-index: 100;
  flex-shrink: 0;
  transition: all var(--transition-speed);
  border-bottom: 1px solid var(--border-color);
  position: relative;
}

.logo {
  display: flex;
  align-items: center;
}

.logo-image {
  height: 38px;
  margin-right: 14px;
  transition: height var(--transition-speed);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.logo h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  transition: font-size var(--transition-speed);
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.5px;
}

.header-controls {
  display: flex;
  gap: 12px;
}

.header-controls .el-button {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.header-controls .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.main-content {
  flex: 1;
  display: grid;
  grid-template-columns: 300px 1fr;
  overflow: hidden;
  min-height: 0; /* 确保网格中的内容可以滚动 */
  transition: grid-template-columns var(--transition-speed);
  gap: 1px;
  background-color: var(--border-color);
}

.compact-mode .main-content {
  grid-template-columns: 260px 1fr;
}

.sidebar {
  height: 100%;
  background-color: var(--background-color);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0; /* 确保flex内容可以滚动 */
  transition: background-color var(--transition-speed);
  animation: fadeIn 0.5s ease-out;
}

.video-area {
  position: relative;
  height: 100%;
  background-color: var(--background-color);
  overflow: hidden;
  transition: background-color var(--transition-speed);
}

.empty-state {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--secondary-text-color);
  text-align: center;
  padding: 0 20px;
  transition: color var(--transition-speed);
  animation: slideUp 0.6s ease-out;
}

.empty-icon {
  font-size: 72px;
  margin-bottom: 24px;
  opacity: 0.6;
  color: var(--primary-color);
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.empty-state h2 {
  font-weight: 600;
  margin-bottom: 12px;
  font-size: 1.6rem;
  background: linear-gradient(135deg, var(--text-color), var(--secondary-text-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.empty-state p {
  opacity: 0.8;
  font-size: 1.1rem;
  max-width: 400px;
  line-height: 1.6;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(6px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  color: white;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s, visibility 0.3s;
}

.app-container.loading .loading-overlay {
  opacity: 1;
  visibility: visible;
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
  box-shadow: 0 0 20px rgba(62, 132, 248, 0.5);
}

.loading-overlay p {
  font-size: 1.2rem;
  font-weight: 500;
  letter-spacing: 1px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}
</style>
